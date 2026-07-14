<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Slot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    /**
     * Flow C — Book a session with an available specialist slot.
     *
     * Concurrency: the slot is locked FOR UPDATE inside a transaction and the
     * availability check happens under that lock, so two simultaneous requests
     * for the same slot cannot both succeed. The loser gets a 409.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:slots,id'],
        ]);

        $appointment = DB::transaction(function () use ($data, $request) {
            /** @var Slot $slot */
            $slot = Slot::query()
                ->whereKey($data['slot_id'])
                ->lockForUpdate()
                ->first();

            if (! $slot->isAvailable()) {
                // Signals to store() that the slot was already taken.
                return null;
            }

            $slot->update(['status' => Slot::STATUS_BOOKED]);

            return Appointment::create([
                'user_id' => $request->user()->id,
                'slot_id' => $slot->id,
                'status' => Appointment::STATUS_SCHEDULED,
            ]);
        });

        if ($appointment === null) {
            return response()->json([
                'message' => 'Ese horario ya no está disponible.',
            ], 409);
        }

        return response()->json([
            'message' => 'Sesión agendada.',
            'appointment' => $appointment->only(['id', 'slot_id', 'status']),
        ], 201);
    }

    /**
     * Flow C — Cancel a session. The slot is released back to available.
     */
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => Appointment::STATUS_CANCELLED]);
            $appointment->slot->update(['status' => Slot::STATUS_AVAILABLE]);
        });

        return response()->json(['message' => 'Sesión cancelada y horario liberado.']);
    }
}
