<?php

namespace App\Http\Requests\Admin;

/**
 * Incidents have no unique slug, so the create rules apply unchanged on update.
 */
class UpdateIncidentRequest extends StoreIncidentRequest {}
