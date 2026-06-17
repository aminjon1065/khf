<?php

namespace App\Http\Requests\Admin;

/**
 * Statistics have no per-locale slug, so update validation is identical to creation.
 */
class UpdateStatisticRequest extends StoreStatisticRequest {}
