<?php

namespace Leat\Domain\Services;

use Leat\Domain\Interfaces\WPPromotionRuleRepositoryInterface;
use Leat\Infrastructure\Constants\WCCoupons;
use Leat\Utils\Coupons;
use Leat\Utils\Logger;
use Leat\Utils\Post;

/**
 * Handles promotion rules management and operations.
 *
 * This class manages promotion rules including CRUD operations, formatting,
 * and coupon-related functionality for the Leat CRM system.
 *
 */
class PromotionRulesService
{
	/**
	 * Repository instance.
	 *
	 * @var WPPromotionRuleRepositoryInterface
	 */
	private $repository;

	/**
	 * Coupons instance.
	 *
	 * @var Coupons
	 */
	private $coupons;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct(WPPromotionRuleRepositoryInterface $repository)
	{
		$this->repository = $repository;
		$this->coupons = new Coupons();
		$this->logger     = new Logger();
	}

	/**
	 * Retrieves a promotion rule by its ID.
	 *
	 * @param int $id The promotion rule ID.
	 * @return array|null Formatted promotion rule data or null if not found.
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
	 * Retrieves all active promotion rules.
	 *
	 * @return array Array of formatted active promotion rules.
	 */
	public function get_active_rules(): array
	{
		return $this->repository->get_active_rules();
	}

	/**
	 * Creates or updates a promotion rule from Leat promotion data.
	 *
	 * @param array    $promotion        The promotion data.
	 * @param int|null $existing_post_id Optional. The existing post ID to update.
	 */
	public function create_or_update(array $promotion, ?int $existingPostId = null): void
	{
		$this->repository->create_or_update($promotion, $existingPostId);
	}

	/**
	 * Deletes a promotion rule by its Leat UUID.
	 *
	 * @param string $uuid The Leat promotion UUID.
	 */
	public function delete($uuid): void
	{
		$this->repository->delete($uuid);
	}

	/**
	 * Handles duplicate promotion rules by removing extras.
	 * Keeps the most recent rule for each UUID and deletes any duplicates.
	 *
	 * @param array $uuids Array of UUIDs to check for duplicates.
	 */
	public function handle_duplicates(array $uuids): void
	{
		$this->repository->handle_duplicates($uuids);
	}

	/**
	 * Deletes promotion rules that have empty UUIDs.
	 *
	 * @return int Number of deleted promotion rules.
	 */
	public function delete_with_empty_uuid(): int
	{
		return $this->repository->delete_with_empty_uuid();
	}

	/**
	 * Retrieves valid coupons for a specific user.
	 * Fetches and validates coupons associated with a user, checking expiration
	 * dates and usage limits.
	 *
	 * @param int $user_id The WordPress user ID.
	 * @return array Array of valid coupon data with associated promotion rules.
	 */
	public function get_coupons_by_user_id($user_id): array
	{
		$user = get_user_by('id', $user_id);

		if (!$user || is_wp_error($user)) {
			return [];
		}

		$coupon_codes = array();
		$coupons = $this->coupons->get_coupons_for_user($user);

		foreach ($coupons as $coupon) {
			$post_id = Post::get_post_meta_data($coupon['id'], WCCoupons::PROMOTION_RULE_ID);

			if (!$post_id) {
				$this->logger->error('No post ID found for coupon ' . $coupon['code']);

				continue;
			}

			$coupon_codes[] = array(
				'type' => 'promotion_rule',
				'code' => $coupon['code'],
				'rule' => $this->get_by_id($post_id),
			);
		}

		return $coupon_codes;
	}

	/**
	 * Converts internal discount type to WooCommerce discount type.
	 *
	 * @param string $value The internal discount type ('percentage' or 'fixed').
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
}
