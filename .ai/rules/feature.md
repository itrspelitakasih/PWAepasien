---
paths:
  - 'tests/Feature/**'
---

# Feature

## Route objects cache their resolved controller — don't rebind a mock mid-test for the same route
Laravel's `Route::getController()` caches the resolved controller instance on the `Route` object after first dispatch. Within a single test method, the router/route collection persists across multiple `$this->post()`/`$this->get()` calls, so hitting the *same* named route twice reuses the controller (and its already-injected constructor dependencies) from the first call — a `$this->mock(SomeDependency::class, ...)` rebind done between the two calls is silently ignored for that route.

Symptom: a mock's `shouldReceive(...)->once()` fails with "should be called exactly 1 times but called 2 times" even though the calls are split correctly across two requests — because both requests actually used the mock instance from the *first* resolution, not the rebound one.

Fix: if a test needs to hit the same route twice (e.g. testing throttling), set expectations on ONE mock for the total call count (e.g. `->twice()`), rather than trying to swap the mock between requests. See tests/Feature/Patient/PatientOtpLoginTest.php::test_requesting_a_second_otp_immediately_is_throttled.
