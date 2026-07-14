<?php

use App\Models\Appointment;
use App\Models\Slot;
use App\Models\Specialist;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Flow C — Scheduling
|--------------------------------------------------------------------------
| - Patient books a session with an available specialist
| - Another user tries to book the same (now occupied) slot
| - Patient cancels and the slot is freed
*/

function availableSlot(): Slot
{
    return Slot::factory()->for(Specialist::factory())->create([
        'status' => Slot::STATUS_AVAILABLE,
    ]);
}

it('books a session with an available specialist', function () {
    $user = User::factory()->create();
    $slot = availableSlot();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/appointments', ['slot_id' => $slot->id])
        ->assertCreated()
        ->assertJsonPath('appointment.status', Appointment::STATUS_SCHEDULED);

    $this->assertDatabaseHas('appointments', [
        'user_id' => $user->id,
        'slot_id' => $slot->id,
        'status' => Appointment::STATUS_SCHEDULED,
    ]);
    expect($slot->fresh()->status)->toBe(Slot::STATUS_BOOKED);
});

it('rejects booking a slot that is already taken', function () {
    $slot = availableSlot();

    // First patient books it.
    $first = User::factory()->create();
    $this->actingAs($first, 'sanctum')
        ->postJson('/api/appointments', ['slot_id' => $slot->id])
        ->assertCreated();

    // Second patient tries the same slot -> 409 Conflict.
    $second = User::factory()->create();
    $this->actingAs($second, 'sanctum')
        ->postJson('/api/appointments', ['slot_id' => $slot->id])
        ->assertStatus(409);

    // Only one appointment exists for that slot.
    expect(Appointment::where('slot_id', $slot->id)->count())->toBe(1);
});

it('frees the slot when the patient cancels', function () {
    $user = User::factory()->create();
    $slot = availableSlot();

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/appointments', ['slot_id' => $slot->id])
        ->assertCreated();

    $appointmentId = $create->json('appointment.id');

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/appointments/{$appointmentId}")
        ->assertOk();

    expect($slot->fresh()->status)->toBe(Slot::STATUS_AVAILABLE);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointmentId,
        'status' => Appointment::STATUS_CANCELLED,
    ]);

    // The freed slot can be booked again (by anyone).
    $other = User::factory()->create();
    $this->actingAs($other, 'sanctum')
        ->postJson('/api/appointments', ['slot_id' => $slot->id])
        ->assertCreated();
});

it('cannot cancel another user\'s appointment', function () {
    $owner = User::factory()->create();
    $slot = availableSlot();

    $appointmentId = $this->actingAs($owner, 'sanctum')
        ->postJson('/api/appointments', ['slot_id' => $slot->id])
        ->json('appointment.id');

    $intruder = User::factory()->create();
    $this->actingAs($intruder, 'sanctum')
        ->deleteJson("/api/appointments/{$appointmentId}")
        ->assertStatus(403);
});
