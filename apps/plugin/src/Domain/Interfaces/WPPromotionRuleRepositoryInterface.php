<?php

namespace Leat\Domain\Interfaces;

interface WPPromotionRuleRepositoryInterface
{
    public function get_by_id($id): ?array;
    public function get_by_uuid($uuid): ?array;
    public function get_rules(array $status = ['publish']): array;
    public function get_active_rules(): array;
    public function create_or_update(array $promotion, ?int $existing_post_id = null): void;
    public function delete($uuid): void;
    public function handle_duplicates(array $uuids): void;
    public function delete_with_empty_uuid(): int;
}
