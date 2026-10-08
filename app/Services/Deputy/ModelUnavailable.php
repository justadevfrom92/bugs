<?php

namespace App\Services\Deputy;

/** A model couldn't answer (not configured, unreachable, rejected or declined); the runner tries the fallback. */
class ModelUnavailable extends \RuntimeException {}
