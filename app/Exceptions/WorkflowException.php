<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Action impossible dans l’état actuel (réservation, validation d’un établissement…) ; le message est affiché à l’utilisateur.
 */
class WorkflowException extends RuntimeException {}
