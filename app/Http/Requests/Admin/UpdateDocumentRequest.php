<?php

namespace App\Http\Requests\Admin;

/**
 * Documents have no unique slug, so the create rules apply unchanged on update.
 */
class UpdateDocumentRequest extends StoreDocumentRequest {}
