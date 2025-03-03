<?php

namespace Leat\Domain\Services;

use Leat\Domain\Interfaces\WPSpendRuleRepositoryInterface;
use Leat\Infrastructure\Constants\WCCoupons;
use Leat\Utils\Coupons;
use Leat\Utils\Logger;
use Leat\Utils\Post;

/**
 * Handles spend rule management and operations.
 *
 * This class manages spend rules for the loyalty program, including creation,
 * retrieval, formatting, and coupon generation for spend rules.
 *
 * @package Leat\Domain\Services
 */
class SpendRulesService
{
    /**
     * Repository instance.
     *
     * @var WPSpendRuleRepositoryInterface
     */
    private $repository;

    /**
     * Logger instance for debugging and error tracking.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Initializes a new instance of the SpendRulesService class.
     *
     * @param WPSpendRuleRepositoryInterface $repository The repository instance.
     */
    public function __construct(WPSpendRuleRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->logger = new Logger();
    }

    /**
     * Retrieves spend rules filtered by type.
     *
     * @param string|null $type Optional. The type of spend rule to filter by.
     * @param array $post_status Optional. Array of post statuses to include. Default ['publish'].
     * @return array|null Array of formatted spend rules or null if none found.
     */
    public function get_spend_rules_by_type($type, $post_status = ['publish']): ?array
    {
        return $this->repository->get_rules_by_type($type, $post_status);
    }

    /**
     * Retrieves a spend rule by its ID.
     *
     * @param int $id The spend rule post ID.
     * @return array|null Formatted spend rule data or null if not found.
     */
    public function get_by_id($id): ?array
    {
        return $this->repository->get_by_id($id);
    }

    /**
     * Retrieves a promotion rule by its Leat UUID.
     *
     * @param string $uuid The Leat promotion UUID.
     * @return array|null Formatted promotion rule data or null if not found.
     */
    public function get_by_uuid($uuid): ?array
    {
        return $this->repository->get_by_uuid($uuid);
    }

    /**
     * Retrieves all promotion rules.
     *
     * @param array $status The status of the promotion rules to retrieve.
     * @return array Array of formatted promotion rules.
     */
    public function get_rules(array $status = ['publish']): array
    {
        return $this->repository->get_rules($status);
    }

    /**
     * Retrieves a spend rule by its Leat UUID.
     *
     * @param string $uuid The Leat reward UUID.
     * @return array|null Formatted spend rule data or null if not found.
     */
    public function get_spend_rule_by_leat_uuid($uuid): ?array
    {
        return $this->repository->get_by_uuid($uuid);
    }

    /**
     * Retrieves all applicable spend rules for a specific contact.
     *
     * This method cross-references spend rules with the rewards available
     * for the specified contact from the Leat API.
     *
     * @param string $contact_uuid The UUID of the contact to get rules for.
     * @param \Leat\Api\Connection $connection The API connection instance.
     * @return array Array of applicable spend rules for the contact.
     */
    public function get_rules_for_contact(string $contact_uuid, \Leat\Api\Connection $connection): array
    {
        // Get all available spend rules
        $all_rules = $this->get_rules();

        if (empty($all_rules)) {
            return [];
        }

        // Get rewards for the contact from the API
        $contact_rewards = $connection->get_rewards_for_contact($contact_uuid);

        if (empty($contact_rewards)) {
            return [];
        }

        // Extract reward UUIDs for easier comparison
        $reward_uuids = array_map(function ($reward) {
            return $reward['uuid'];
        }, $contact_rewards);

        // Filter spend rules to only include those with matching reward UUIDs
        $applicable_rules = array_filter($all_rules, function ($rule) use ($reward_uuids) {
            // Check if the rule has a selected reward that matches any of the contact's rewards
            if (isset($rule['selectedReward']['value']) && in_array($rule['selectedReward']['value'], $reward_uuids)) {
                return true;
            }

            // Also check if the rule's UUID directly matches any reward UUID
            // This handles cases where the rule itself is the reward
            if (isset($rule['uuid']['value']) && in_array($rule['uuid']['value'], $reward_uuids)) {
                return true;
            }

            return false;
        });

        return array_values($applicable_rules); // Reset array keys
    }

    /**
     * Creates or updates a spend rule from Leat promotion data.
     *
     * @param array    $promotion        The promotion data.
     * @param int|null $existing_post_id Optional. The existing post ID to update.
     */
    public function create_or_update(array $promotion, ?int $existing_post_id = null): void
    {
        $this->repository->create_or_update($promotion, $existing_post_id);
    }

    /**
     * Deletes a spend rule by its Leat UUID.
     *
     * @param string $uuid The Leat reward UUID to find and delete.
     * @return void
     */
    public function delete_spend_rule_by_leat_uuid($uuid): void
    {
        $this->repository->delete_by_uuid($uuid);
    }

    /**
     * Gets the applicable spend rule for a given credit amount.
     *
     * Finds the spend rule with the highest credit cost that is still within
     * the user's available credit amount.
     *
     * @param int $credit_amount The available credit amount.
     * @return array|null The applicable spend rule, or null if none found.
     */
    public function get_applicable_spend_rule($credit_amount): ?array
    {
        return $this->repository->get_applicable_rule($credit_amount);
    }

    /**
     * Creates or updates a spend rule based on a Leat reward.
     *
     * @param array $reward The Leat reward data.
     * @param int|null $existing_post_id Optional. Existing post ID to update.
     * @return void
     */
    public function create_or_update_spend_rule_from_reward($reward, $existing_post_id = null): void
    {
        $this->repository->create_or_update_from_reward($reward, $existing_post_id);
    }

    /**
     * Converts the discount type value to WooCommerce format.
     *
     * @param string $value The discount type value ('percentage' or 'fixed').
     * @return string|null The WooCommerce discount type or null if invalid.
     */
    public function get_discount_type($value): ?string
    {
        if ('percentage' === $value) {
            return 'percent';
        } elseif ('fixed' === $value) {
            return 'fixed_product';
        }

        return null;
    }

    /**
     * Creates a WooCommerce coupon for a spend rule.
     *
     * @param array $formatted_spend_rule The formatted spend rule data.
     * @param int|null $user_id Optional. The user ID to restrict the coupon to.
     * @return string The generated coupon code.
     */
    public function create_coupon_for_spend_rule($formatted_spend_rule, $user_id): string
    {
        $coupon_code = wp_generate_uuid4();

        $existing_coupon = new \WC_Coupon($coupon_code);

        if ($existing_coupon) {
            $coupon_code = wp_generate_uuid4();
        }

        $rule_amount = $formatted_spend_rule['discountValue']['value'];
        $rule_limit_usage_to_x_items = $formatted_spend_rule['limitUsageToXItems']['value'];

        $coupon = new \WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_description('Leat Spend Rule: ' . $formatted_spend_rule['title']['value']);
        $coupon->set_usage_limit(1);
        $coupon->set_individual_use(true);

        $coupon->add_meta_data('_leat_spend_rule_coupon', 'true', true);
        $coupon->add_meta_data(WCCoupons::SPEND_RULE_ID, $formatted_spend_rule['id'], true);

        $discount_type = $formatted_spend_rule['discountType']['value'];

        if ($user_id) {
            $user       = get_user_by('id', $user_id);
            $user_email = $user->user_email;

            $coupon->add_meta_data('_leat_user_id', $user_id, true);
            $coupon->set_email_restrictions([$user_email]);
        }

        switch ($formatted_spend_rule['type']['value']) {
            case 'FREE_PRODUCT':
                $coupon->set_amount(0);

                if ('fixed' === $discount_type) {
                    $coupon->set_discount_type('fixed_product');
                } else {
                    $coupon->set_discount_type('percent');
                }

                // Set product IDs for product restrictions.
                // Note: For 100% discounts, we intentionally skip setting product IDs due to
                // a WooCommerce Store API limitation where product restrictions aren't applied.
                if (
                    !empty($formatted_spend_rule['selectedProducts']['value']) &&
                    intval($rule_amount) !== 100
                ) {
                    $coupon->set_product_ids($formatted_spend_rule['selectedProducts']['value']);
                }

                break;
            case 'ORDER_DISCOUNT':
                $coupon->set_amount($rule_amount);

                if ('fixed' === $discount_type) {
                    $coupon->set_discount_type('fixed_cart');
                } else {
                    $coupon->set_discount_type('percent');
                }

                break;
            case 'FREE_SHIPPING':
                $coupon->set_discount_type('fixed_cart');
                $coupon->set_amount(0);
                $coupon->set_free_shipping(true);

                break;
            case 'CATEGORY':
                $coupon->set_amount($rule_amount);

                // Set product categories.
                if (!empty($formatted_spend_rule['selectedCategories']['value'])) {
                    $coupon->set_product_categories($formatted_spend_rule['selectedCategories']['value']);
                }

                // Add limit usage to X items if set.
                if (isset($rule_limit_usage_to_x_items)) {
                    $limit = $rule_limit_usage_to_x_items;

                    if ($limit) {
                        $coupon->set_limit_usage_to_x_items(intval($limit));
                    } else {
                        // By default, always limit usage to 1 item.
                        $coupon->set_limit_usage_to_x_items(1);
                    }
                }

                break;
        }

        // Check for minimum purchase amount.
        if (
            isset($formatted_spend_rule['minimumPurchaseAmount']) &&
            is_numeric($formatted_spend_rule['minimumPurchaseAmount']['value'])
        ) {
            $min_amount = floatval($formatted_spend_rule['minimumPurchaseAmount']['value']);
            if ($min_amount > 0) {
                $coupon->set_minimum_amount($min_amount);
            }
        }

        $coupon->save();

        return $coupon_code;
    }

    /**
     * Retrieves all valid and usable coupons for a specific user.
     *
     * @param int $user_id The user ID.
     * @return array List of valid coupons with their associated spend rules.
     */
    public function get_coupons_by_user_id($user_id): array
    {
        $user = get_user_by('id', $user_id);

        if (!$user || is_wp_error($user)) {
            return [];
        }

        $coupon_codes = array();
        $coupons = Coupons::get_coupons_for_user($user);

        foreach ($coupons as $coupon) {
            $post_id = Post::get_post_meta_data($coupon['id'], WCCoupons::SPEND_RULE_ID);

            if (!$post_id) {
                continue;
            }

            $coupon_codes[] = array(
                'type' => 'spend_rule',
                'code' => $coupon['code'],
                'rule' => $this->get_by_id($post_id),
            );
        }

        return $coupon_codes;
    }
}
