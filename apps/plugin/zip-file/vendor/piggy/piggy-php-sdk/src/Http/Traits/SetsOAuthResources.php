<?php

namespace Piggy\Api\Http\Traits;

use Piggy\Api\Http\BaseClient;
use Piggy\Api\Resources\OAuth\Automations\AutomationsResource;
use Piggy\Api\Resources\OAuth\Brandkit\BrandkitResource;
use Piggy\Api\Resources\OAuth\Contacts\ContactAttributesResource;
use Piggy\Api\Resources\OAuth\Contacts\ContactIdentifiersResource;
use Piggy\Api\Resources\OAuth\Contacts\ContactsResource;
use Piggy\Api\Resources\OAuth\Contacts\ContactVerificationResource;
use Piggy\Api\Resources\OAuth\ContactsPortal\ContactsPortalAuthUrlResource;
use Piggy\Api\Resources\OAuth\ContactSubscriptionsResource;
use Piggy\Api\Resources\OAuth\CustomAttributeResource;
use Piggy\Api\Resources\OAuth\Forms\FormsResource;
use Piggy\Api\Resources\OAuth\Giftcards\GiftcardsResource;
use Piggy\Api\Resources\OAuth\Giftcards\GiftcardTransactionsResource;
use Piggy\Api\Resources\OAuth\Giftcards\Program\GiftcardProgramsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Program\LoyaltyProgramsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Receptions\CreditReceptionsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Receptions\LoyaltyTransactionAttributesResource;
use Piggy\Api\Resources\OAuth\Loyalty\Receptions\LoyaltyTransactionsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Receptions\RewardReceptionsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Rewards\CollectableRewardsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Rewards\RewardAttributesResource;
use Piggy\Api\Resources\OAuth\Loyalty\Rewards\RewardsResource;
use Piggy\Api\Resources\OAuth\Loyalty\Tokens\LoyaltyTokensResource;
use Piggy\Api\Resources\OAuth\Perks\PerksResource;
use Piggy\Api\Resources\OAuth\PortalSessions\PortalSessionsResource;
use Piggy\Api\Resources\OAuth\PrepaidTransactionsResource;
use Piggy\Api\Resources\OAuth\Shops\ShopsResource;
use Piggy\Api\Resources\OAuth\SubscriptionTypesResource;
use Piggy\Api\Resources\OAuth\Tiers\TiersResource;
use Piggy\Api\Resources\OAuth\Units\UnitsResource;
use Piggy\Api\Resources\OAuth\Vouchers\PromotionAttributesResource;
use Piggy\Api\Resources\OAuth\Vouchers\PromotionsResource;
use Piggy\Api\Resources\OAuth\Vouchers\VouchersResource;
use Piggy\Api\Resources\OAuth\WebhookSubscriptions\WebhookSubscriptionsResource;

/**
 * Trait SetsOAuthResources
 */
trait SetsOAuthResources
{
    /**
     * @var ContactsResource
     */
    public $contacts;

    /**
     * @var ContactIdentifiersResource
     */
    public $contactIdentifiers;

    /**
     * @var ContactVerificationResource
     */
    public $contactVerification;

    /**
     * @var ContactSubscriptionsResource
     */
    public $contactSubscriptions;

    /**
     * @var SubscriptionTypesResource
     */
    public $subscriptionTypes;

    /**
     * @var ShopsResource
     */
    public $shops;

    /**
     * @var CreditReceptionsResource
     */
    public $creditReceptions;

    /**
     * @var RewardsResource
     */
    public $rewards;

    /**
     * @var GiftcardsResource;
     */
    public $giftcards;

    /**
     * @var GiftcardTransactionsResource
     */
    public $giftcardTransactions;

    /**
     * @var LoyaltyTransactionsResource
     */
    public $loyaltyTransactions;

    /**
     * @var PrepaidTransactionsResource
     */
    public $prepaidTransactions;

    /**
     * @var RewardReceptionsResource
     */
    public $rewardReceptions;

    /**
     * @var AutomationsResource
     */
    public $automations;

    /**
     * @var UnitsResource
     */
    public $units;

    /** @var LoyaltyProgramsResource */
    public $loyaltyProgram;

    /** @var GiftcardProgramsResource */
    public $giftcardProgram;

    /**
     * @var ContactAttributesResource
     */
    public $contactAttributes;

    /**
     * @var RewardAttributesResource
     */
    public $rewardAttributes;

    /**
     * @var LoyaltyTokensResource
     */
    public $loyaltyToken;

    /**
     * @var PromotionsResource
     */
    public $promotion;

    /**
     * @var PromotionAttributesResource
     */
    public $promotionAttributes;

    /**
     * @var VouchersResource
     */
    public $voucher;

    /**
     * @var BrandkitResource
     */
    public $brandkit;

    /**
     * @var TiersResource
     */
    public $tier;

    /**
     * @var PerksResource
     */
    public $perk;

    /**
     * @var PortalSessionsResource
     */
    public $portalSessions;

    /**
     * @var FormsResource
     */
    public $forms;

    /**
     * @var LoyaltyTransactionAttributesResource
     */
    public $loyaltyTransactionAttributes;

    /**
     * @var WebhookSubscriptionsResource
     */
    public $webhookSubscriptions;

    /**
     * @var ContactsPortalAuthUrlResource
     */
    public $contactsPortalAuthUrl;

    /**
     * @var CollectableRewardsResource
     */
    public $collectableRewards;

    /**
     * @var CustomAttributeResource
     */
    public $customAttributes;

    protected function setResources(BaseClient $client): void
    {
        $this->contacts = new ContactsResource($client);
        $this->contactIdentifiers = new ContactIdentifiersResource($client);
        $this->contactAttributes = new ContactAttributesResource($client);
        $this->giftcards = new GiftcardsResource($client);
        $this->giftcardTransactions = new GiftcardTransactionsResource($client);
        $this->giftcardProgram = new GiftcardProgramsResource($client);
        $this->shops = new ShopsResource($client);
        $this->rewards = new RewardsResource($client);
        $this->contactVerification = new ContactVerificationResource($client);
        $this->prepaidTransactions = new PrepaidTransactionsResource($client);
        $this->rewardReceptions = new RewardReceptionsResource($client);
        $this->loyaltyTransactions = new LoyaltyTransactionsResource($client);
        $this->contactSubscriptions = new ContactSubscriptionsResource($client);
        $this->subscriptionTypes = new SubscriptionTypesResource($client);
        $this->creditReceptions = new CreditReceptionsResource($client);
        $this->automations = new AutomationsResource($client);
        $this->units = new UnitsResource($client);
        $this->loyaltyProgram = new LoyaltyProgramsResource($client);
        $this->rewardAttributes = new RewardAttributesResource($client);
        $this->loyaltyToken = new LoyaltyTokensResource($client);
        $this->promotion = new PromotionsResource($client);
        $this->promotionAttributes = new PromotionAttributesResource($client);
        $this->voucher = new VouchersResource($client);
        $this->brandkit = new BrandkitResource($client);
        $this->tier = new TiersResource($client);
        $this->perk = new PerksResource($client);
        $this->portalSessions = new PortalSessionsResource($client);
        $this->forms = new FormsResource($client);
        $this->loyaltyTransactionAttributes = new LoyaltyTransactionAttributesResource($client);
        $this->webhookSubscriptions = new WebhookSubscriptionsResource($client);
        $this->contactsPortalAuthUrl = new ContactsPortalAuthUrlResource($client);
        $this->collectableRewards = new CollectableRewardsResource($client);
        $this->customAttributes = new CustomAttributeResource($client);
    }
}
