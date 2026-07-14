<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;

/**
 * Video gateway (AWS Chime SDK Meetings).
 *
 * Chime depends on real peripherals and the user's network, so it can't run in
 * CI. When CHIME_ENABLED=true with AWS creds it calls the real API; otherwise it
 * returns a deterministic stub with the same CreateMeeting / CreateAttendee
 * shape the Flutter/Vue client consumes.
 */
class ChimeGateway
{
    public function createMeeting(Appointment $appointment, User $user): array
    {
        if (config('services.chime.enabled') && config('services.chime.key')) {
            return $this->createReal($appointment, $user);
        }

        return $this->stub($appointment, $user);
    }

    private function stub(Appointment $appointment, User $user): array
    {
        $region = config('services.chime.region', 'us-east-1');
        $meetingId = 'meeting-'.$appointment->id.'-'.substr(md5((string) $appointment->id), 0, 8);
        $attendeeId = 'att-'.$user->id.'-'.substr(md5((string) $user->id), 0, 8);

        return [
            'Meeting' => [
                'MeetingId' => $meetingId,
                'MediaRegion' => $region,
                'MediaPlacement' => [
                    'AudioHostUrl' => "stub-audio.{$region}.chime.aws:3478",
                    'SignalingUrl' => "wss://stub-signal.{$region}.chime.aws",
                    'TurnControlUrl' => "https://stub-turn.{$region}.chime.aws",
                ],
            ],
            'Attendee' => [
                'AttendeeId' => $attendeeId,
                'ExternalUserId' => (string) $user->id,
                'JoinToken' => 'join_'.$attendeeId,
            ],
        ];
    }

    private function createReal(Appointment $appointment, User $user): array
    {
        $client = new \Aws\ChimeSDKMeetings\ChimeSDKMeetingsClient([
            'version' => 'latest',
            'region' => config('services.chime.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.chime.key'),
                'secret' => config('services.chime.secret'),
            ],
        ]);

        $meeting = $client->createMeeting([
            'ClientRequestToken' => 'appt-'.$appointment->id,
            'MediaRegion' => config('services.chime.region', 'us-east-1'),
            'ExternalMeetingId' => 'appt-'.$appointment->id,
        ]);

        $attendee = $client->createAttendee([
            'MeetingId' => $meeting['Meeting']['MeetingId'],
            'ExternalUserId' => (string) $user->id,
        ]);

        return [
            'Meeting' => $meeting['Meeting'],
            'Attendee' => $attendee['Attendee'],
        ];
    }
}
