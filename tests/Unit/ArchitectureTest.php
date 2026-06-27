<?php

arch('debug helpers are not used in application code')
    ->expect([
        'dd',
        'dump',
        'ray',
        'var_dump',
    ])
    ->not->toBeUsed();
