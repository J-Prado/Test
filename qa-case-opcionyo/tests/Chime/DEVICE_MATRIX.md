# AWS Chime — Device / OS / Browser test matrix

Real Chime sessions depend on physical mics/cameras, OS permission models, and
the user's network — none of which can run in a CI pipeline. So we split the
problem in two:

1. **Logic** (meeting/attendee orchestration, token handling, error/reconnect
   paths) → automated with a mocked Chime SDK. See `tests/Feature/ChimeVideoTest.php`.
2. **Media / device reality** → a prioritised **manual + device-farm** matrix
   (below). We don't run these in CI; we run them on a real-device cloud
   (BrowserStack / AWS Device Farm) before each release that touches video.

## How priority was assigned

Priority = (share of the ~11k monthly sessions on that combo) × (risk that the
combo behaves differently for audio/video capture & permissions). Safari/iOS and
in-app webviews carry the most Chime-specific quirks, so they rank highest even
at moderate traffic.

| # | Device / OS | Browser / runtime | Why it's critical | Priority |
|---|-------------|-------------------|-------------------|----------|
| 1 | iPhone (iOS 16+) | Safari mobile | Strictest autoplay/getUserMedia rules; must tap-to-start audio; largest patient share on mobile | 🔴 P0 |
| 2 | Android (12+) | Chrome mobile | Fragmented mic/camera permission prompts across OEMs | 🔴 P0 |
| 3 | Windows 11 | Chrome | Largest specialist (desktop) share; screen/camera device switching | 🔴 P0 |
| 4 | macOS | Safari | WebRTC codec + permission differences vs Chrome | 🟠 P1 |
| 5 | Windows 11 | Edge | Chromium but different media device enumeration in enterprise setups | 🟠 P1 |
| 6 | iOS | Flutter WebView / in-app | App embeds the call; webview lacks some getUserMedia affordances | 🟠 P1 |
| 7 | macOS | Chrome | Common specialist combo; baseline happy path | 🟡 P2 |
| 8 | Android | Firefox mobile | Low share, different WebRTC stack | 🟢 P3 |

## Scenarios run against each combo (media layer)

- Grant mic+camera → join → both peers see/hear each other.
- **Deny** camera permission → call still connects audio-only, clear UI state.
- Switch input device mid-call (e.g. AirPods connect) → stream recovers.
- Network drop / switch Wi-Fi↔cellular → auto-reconnect within N seconds.
- Background the app (mobile) then return → session resumes or reconnects.
- Two attendees, one leaves → the other stays connected.

## What we automate vs. observe

| Layer | Tooling | Where |
|-------|---------|-------|
| Meeting/attendee creation, tokens, error mapping, reconnect logic | Mocked Chime SDK (Pest) | CI, every PR |
| Permission-denied / device-switch **UI states** | Playwright with fake media devices (`--use-fake-device-for-media-stream`) | CI (optional job) / nightly |
| Real audio/video capture on real hardware | Manual + device farm | Pre-release, video-touching changes |
