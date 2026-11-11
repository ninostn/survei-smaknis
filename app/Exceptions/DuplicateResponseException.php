<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Respons ganda untuk survei yang sama, terdeteksi lewat unique constraint.
 */
class DuplicateResponseException extends RuntimeException {}
