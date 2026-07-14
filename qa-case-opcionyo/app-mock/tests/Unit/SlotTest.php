<?php

use App\Models\Slot;

it('reports availability based on status', function () {
    $available = new Slot(['status' => Slot::STATUS_AVAILABLE]);
    $booked = new Slot(['status' => Slot::STATUS_BOOKED]);

    expect($available->isAvailable())->toBeTrue();
    expect($booked->isAvailable())->toBeFalse();
});
