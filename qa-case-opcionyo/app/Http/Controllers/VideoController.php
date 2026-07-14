<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\ChimeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    // Video — create a Chime meeting for a booked appointment.
    public function create(Request $request, ChimeGateway $chime): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
        ]);

        $appointment = Appointment::findOrFail($data['appointment_id']);

        if ($appointment->patient_id !== $request->user()->id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        if ($appointment->status !== Appointment::STATUS_BOOKED) {
            return response()->json(['error' => 'no_active_appointment'], 422);
        }

        return response()->json($chime->createMeeting($appointment, $request->user()), 201);
    }
}
