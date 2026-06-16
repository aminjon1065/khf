<?php

namespace App\Http\Requests\Admin;

/**
 * Leaders have no per-locale slug, so update validation is identical to creation.
 */
class UpdateLeaderRequest extends StoreLeaderRequest {}
