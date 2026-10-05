<?php

return [
    'repair' => filter_var(env('REPAIR', true), FILTER_VALIDATE_BOOLEAN),
    'wholesale' => filter_var(env('WHOLESALE', true), FILTER_VALIDATE_BOOLEAN),
];
