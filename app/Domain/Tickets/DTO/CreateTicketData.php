<?php

namespace App\Domain\Tickets\DTO;

use Illuminate\Http\UploadedFile;

class CreateTicketData
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $createdById,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $priority,
        public readonly string $channel,
        /** @var UploadedFile[] */
        public readonly array $attachments = [],
    ) {
    }
}
