<?php

use App\Models\Appointment;
use App\Models\Slot;
use App\Models\Specialist;
use App\Models\User;
use App\Services\ChimeService;
use Aws\ChimeSDKMeetings\ChimeSDKMeetingsClient;
use Aws\ChimeSDKMeetings\Exception\ChimeSDKMeetingsException;
use Aws\Command;
use Aws\MockHandler;
use Aws\Result;

/*
|--------------------------------------------------------------------------
| Video calls — AWS Chime (the highest-value test)
|--------------------------------------------------------------------------
| Real video depends on hardware + network we cannot drive in CI. What we CAN
| (and must) test is the server-side orchestration: given an appointment,
| create a Chime meeting + attendee and hand back valid join credentials.
|
| We inject a mocked ChimeSDKMeetingsClient (AWS SDK MockHandler) so no real
| AWS call is made. This is the test that would have caught the most prod
| incidents in the "join a call" path.
*/

function chimeClientReturning(MockHandler $handler): ChimeSDKMeetingsClient
{
    return new ChimeSDKMeetingsClient([
        'region' => 'us-east-1',
        'version' => 'latest',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => $handler,
    ]);
}

function makeAppointment(): Appointment
{
    $slot = Slot::factory()->for(Specialist::factory())->booked()->create();

    return Appointment::factory()->create([
        'user_id' => User::factory()->create()->id,
        'slot_id' => $slot->id,
    ]);
}

it('creates a Chime meeting and attendee and returns join credentials', function () {
    $handler = new MockHandler();
    $handler->append(new Result([
        'Meeting' => [
            'MeetingId' => 'meeting-abc-123',
            'MediaRegion' => 'us-east-1',
            'MediaPlacement' => ['AudioHostUrl' => 'https://audio.example'],
        ],
    ]));
    $handler->append(new Result([
        'Attendee' => [
            'AttendeeId' => 'attendee-xyz-789',
            'JoinToken' => 'join-token-secret',
        ],
    ]));

    $service = new ChimeService(chimeClientReturning($handler));
    $session = $service->createSessionForAppointment(makeAppointment());

    expect($session)->toMatchArray([
        'meetingId' => 'meeting-abc-123',
        'attendeeId' => 'attendee-xyz-789',
        'joinToken' => 'join-token-secret',
        'mediaRegion' => 'us-east-1',
    ]);
});

it('surfaces a clean error when Chime fails (no raw AWS error leaks out)', function () {
    $handler = new MockHandler();
    // Simulate AWS throttling / service failure on createMeeting. Appending an
    // exception makes the mocked client reject (throw) on the call.
    $handler->append(new ChimeSDKMeetingsException(
        'Rate exceeded',
        new Command('CreateMeeting'),
        ['code' => 'ThrottlingException']
    ));

    $service = new ChimeService(chimeClientReturning($handler));

    expect(fn () => $service->createSessionForAppointment(makeAppointment()))
        ->toThrow(RuntimeException::class, 'No se pudo iniciar la videollamada. Intentá nuevamente.');
});
