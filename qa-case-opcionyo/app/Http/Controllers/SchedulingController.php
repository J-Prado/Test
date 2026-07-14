<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Slot;
use App\Models\Specialist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedulingController extends Controller
{
    public function specialists(): JsonResponse
    {
        return response()->json(['specialists' => Specialist::orderBy('name')->get()]);
    }

    public function slots(Request $request): JsonResponse
    {
        $query = Slot::query()->with('specialist')->orderBy('starts_at');

        if ($request->filled('specialist_id')) {
            $query->where('specialist_id', $request->query('specialist_id'));
        }

        return response()->json(['slots' => $query->get()]);
    }

    // The signed-in patient's upcoming (booked) appointments.
    public function myAppointments(Request $request): JsonResponse
    {
        $appointments = Appointment::where('patient_id', $request->user()->id)
            ->where('status', Appointment::STATUS_BOOKED)
            ->with('slot.specialist')
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    // Flow C — book an available slot. Row lock prevents double booking.
    public function book(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:slots,id'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $slot = Slot::whereKey($data['slot_id'])->lockForUpdate()->first();

            if (! $slot->isAvailable()) {
                return response()->json(['error' => 'slot_taken'], 409);
            }

            $appointment = Appointment::create([
                'slot_id' => $slot->id,
                'patient_id' => $request->user()->id,
                'status' => Appointment::STATUS_BOOKED,
            ]);

            $slot->update([
                'status' => Slot::STATUS_BOOKED,
                'appointment_id' => $appointment->id,
            ]);

            return response()->json([
                'appointment' => $appointment->load('slot.specialist'),
            ], 201);
        });
    }

    // Flow C — cancel; frees the slot.
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->patient_id !== $request->user()->id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        if ($appointment->status === Appointment::STATUS_CANCELED) {
            return response()->json(['error' => 'not_found'], 404);
        }

        DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => Appointment::STATUS_CANCELED]);

            $slot = $appointment->slot;
            if ($slot) {
                $slot->update([
                    'status' => Slot::STATUS_AVAILABLE,
                    'appointment_id' => null,
                ]);
            }
        });

        return response()->json([
            'appointment' => $appointment->fresh('slot.specialist'),
        ]);
    }
}
