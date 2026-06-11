<?php

namespace App\Http\Requests\Admin;

/**
 * Guide slugs are auto-derived and unconstrained, so the create rules apply unchanged on update.
 */
class UpdateGuideRequest extends StoreGuideRequest {}
