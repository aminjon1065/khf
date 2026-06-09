<?php

namespace App\Http\Requests\Admin;

/**
 * Alerts have no unique slug, so the create rules apply unchanged on update.
 */
class UpdateAlertRequest extends StoreAlertRequest {}
