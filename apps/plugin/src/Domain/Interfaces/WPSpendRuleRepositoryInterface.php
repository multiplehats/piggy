<?php

namespace Leat\Domain\Interfaces;

interface WPSpendRuleRepositoryInterface
{
    public function get_by_id($id): ?array;
    public function get_by_uuid($uuid): ?array;
    public function get_rules(array $status = ['publish']): array;
    public function get_rules_by_type($type, array $status = ['publish']): ?array;
    public function get_active_rules(): ?array;
    public function create_or_update(array $spend_rule, ?int $existing_post_id = null): void;
    public function create_or_update_from_reward(array $reward, ?int $existing_post_id = null): void;
    public function delete_by_uuid($uuid): void;
    public function get_applicable_rule($credit_amount): ?array;
}
