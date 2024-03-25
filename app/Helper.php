<?php

function npScore($old_value)
{
    $old_min = -4.5;
    $old_max = 4.5;
    $new_min = 0;
    $new_max = 30;

    return ($old_value - $old_min) / ($old_max - $old_min) * ($new_max - $new_min) + $new_min;
}
