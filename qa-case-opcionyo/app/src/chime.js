'use strict';

/**
 * Video calls — AWS Chime SDK Meetings.
 *   POST /api/video/meetings   { appointmentId }  -> meeting + attendee join info
 *
 * Chime depends on real peripherals and the user's network, so it can't run in
 * CI. What we CAN test is the app's own logic: that a booked appointment yields
 * a well-formed meeting/attendee payload with the fields the Flutter/Vue client
 * needs to join. When AWS creds are configured the real SDK is used; otherwise
 * a deterministic stub returns the same response shape as CreateMeeting /
 * CreateAttendee.
 */

const express = require('express');
const db = require('./db');
const { requireAuth } = require('./auth');

const router = express.Router();

const REGION = process.env.AWS_REGION || 'us-east-1';

let chimeClient = null;
let chimeCmds = null;
if (process.env.AWS_ACCESS_KEY_ID && process.env.USE_REAL_CHIME === '1') {
  try {
    // eslint-disable-next-line global-require
    const sdk = require('@aws-sdk/client-chime-sdk-meetings');
    chimeClient = new sdk.ChimeSDKMeetingsClient({ region: REGION });
    chimeCmds = sdk;
  } catch (_) {
    chimeClient = null; // SDK not installed; use the stub.
  }
}

function stubMeeting(appointmentId, patientId) {
  const meetingId = db.nextId('meeting');
  const attendeeId = db.nextId('att');
  const meeting = {
    Meeting: {
      MeetingId: meetingId,
      MediaRegion: REGION,
      MediaPlacement: {
        AudioHostUrl: `stub-audio.${REGION}.chime.aws:3478`,
        SignalingUrl: `wss://stub-signal.${REGION}.chime.aws`,
        TurnControlUrl: `https://stub-turn.${REGION}.chime.aws`,
      },
    },
    Attendee: {
      AttendeeId: attendeeId,
      ExternalUserId: patientId,
      JoinToken: `join_${attendeeId}`,
    },
  };
  db.store.meetings.set(meetingId, { ...meeting, appointmentId });
  return meeting;
}

router.post('/video/meetings', requireAuth, async (req, res) => {
  const { appointmentId } = req.body || {};
  const appt = db.findAppointment(appointmentId);

  if (!appt || appt.status !== 'booked') {
    return res.status(422).json({ error: 'no_active_appointment' });
  }
  if (appt.patientId !== req.user.id) {
    return res.status(403).json({ error: 'not_owner' });
  }

  if (chimeClient && chimeCmds) {
    try {
      const meeting = await chimeClient.send(
        new chimeCmds.CreateMeetingCommand({
          ClientRequestToken: appointmentId,
          MediaRegion: REGION,
          ExternalMeetingId: appointmentId,
        })
      );
      const attendee = await chimeClient.send(
        new chimeCmds.CreateAttendeeCommand({
          MeetingId: meeting.Meeting.MeetingId,
          ExternalUserId: req.user.id,
        })
      );
      return res
        .status(201)
        .json({ Meeting: meeting.Meeting, Attendee: attendee.Attendee });
    } catch (err) {
      return res.status(502).json({ error: 'chime_error', message: err.message });
    }
  }

  return res.status(201).json(stubMeeting(appointmentId, req.user.id));
});

module.exports = { router };
