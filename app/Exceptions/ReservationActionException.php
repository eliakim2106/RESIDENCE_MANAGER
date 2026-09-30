<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Action impossible sur une réservation dans son état actuel ; le message est affiché à l'utilisateur.
 */
class ReservationActionException extends RuntimeException {}
