<?php

namespace App\Http\Requests\Admin;

/**
 * FAQs have no per-locale slug, so update validation is identical to creation.
 */
class UpdateFaqRequest extends StoreFaqRequest {}
