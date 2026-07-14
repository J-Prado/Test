<?php

namespace App\Services;

use App\Models\Appointment;
use Aws\ChimeSDKMeetings\ChimeSDKMeetingsClient;
use Aws\Exception\AwsException;
use RuntimeException;

/**
 * Wraps the AWS Chime SDK Meetings client.
 *
 * Real video calls depend on hardware + network we cannot exercise in CI, so
 * the value here is the *server-side orchestration*: given an appointment,
 * create a Chime meeting and an attendee, and hand back the credentials the
 * client (Vue/Flutter) needs to join. That logic is fully unit-testable by
 * injecting a mocked ChimeSDKMeetingsClient (see tests/Feature/ChimeVideoTest).
 */
class ChimeService
{
    public function __construct(private ChimeSDKMeetingsClient $client) {}

    /**
     * Create a meeting + attendee for an appointment and return join info.
     *
     * @return array{meetingId: string, attendeeId: string, joinToken: string, mediaRegion: string}
     */
    public function createSessionForAppointment(Appointment $appointment): array
    {
        try {
            $meeting = $this->client->createMeeting([
                'ClientRequestToken' => 'appt-'.$appointment->id,
                'ExternalMeetingId' => (string) $appointment->id,
                'MediaRegion' => (string) config('services.chime.region', 'us-east-1'),
            ]);

            $meetingId = $meeting['Meeting']['MeetingId'];

            $attendee = $this->client->createAttendee([
                'MeetingId' => $meetingId,
                'ExternalUserId' => (string) $appointment->user_id,
            ]);

            return [
                'meetingId' => $meetingId,
                'attendeeId' => $attendee['Attendee']['AttendeeId'],
                'joinToken' => $attendee['Attendee']['JoinToken'],
                'mediaRegion' => $meeting['Meeting']['MediaRegion'] ?? (string) config('services.chime.region'),
            ];
        } catch (AwsException $e) {
            // Never leak raw AWS errors to the patient; surface a clean failure
            // the API layer can translate into a friendly "try again" message.
            throw new RuntimeException(
                'No se pudo iniciar la videollamada. Intentá nuevamente.',
                previous: $e
            );
        }
    }
}
