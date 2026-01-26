<?php

namespace App\Domain\Tickets\DTO;

use Illuminate\Http\UploadedFile;

class ReplyTicketData
{
    public function __construct(
        public readonly int $ticketId,
        public readonly int $customerId,
        public readonly string $senderType,
        public readonly ?int $senderId,
        public readonly string $body,
        public readonly bool $isInternal = false,
        /** @var UploadedFile[] */
        public readonly array $attachments = [],
    ) {
    }
}
