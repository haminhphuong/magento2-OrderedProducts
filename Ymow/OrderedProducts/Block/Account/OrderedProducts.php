<?php

namespace Ymow\OrderedProducts\Block\Account;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url\Helper\Data as CoreUrlHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class OrderedProducts extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = "Ymow_OrderedProducts::ordered_products.phtml";

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var SessionFactory
     */
    protected $customerSession;

    /**
     * @var Cart
     */
    private $cartHelper;

    /**
     * @var CoreUrlHelper
     */
    private $urlHelper;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @param Context $context
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProductFactory $productFactory
     * @param SessionFactory $customerSession
     * @param Cart $cartHelper
     * @param CoreUrlHelper $urlHelper
     * @param RedirectInterface $redirect
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurable
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderCollectionFactory $orderCollectionFactory,
        ProductFactory $productFactory,
        SessionFactory $customerSession,
        Cart $cartHelper,
        CoreUrlHelper $urlHelper,
        RedirectInterface $redirect,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
        array $data = []
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productFactory = $productFactory;
        $this->customerSession = $customerSession;
        $this->cartHelper = $cartHelper;
        $this->urlHelper = $urlHelper;
        $this->redirect = $redirect;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->configurable = $configurable;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getOrderedProducts()
    {
        $customerId = $this->customerSession->create()->getCustomerId();
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);
        $skuList = [];
        $products = [];

        foreach ($orderCollection->getItems() as $order) {
            $orderItems = $order->getAllVisibleItems();
            foreach ($orderItems as $item) {
                $sku = $item->getSku();
                if (!in_array($sku, $skuList)) {
                    try {
                        $product = $this->productRepository->get($sku);
                        if ($product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                            $skuList[] = $sku;
                            $products[] = $product;
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        continue;
                    }
                }
            }
        }
        return $products;
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getAddToCartUrl(Product $product): string
    {
        $configs = [
            '_escape' => false
        ];

        if (!$product->getTypeInstance()->isPossibleBuyFromList($product)) {
            $configs['_query'] = [
                'options' => 'cart'
            ];

            return $product->getUrlModel()->getUrl($product, $configs);
        }

        return $this->cartHelper->getAddUrl($product, $configs);
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product): array
    {
        $addToCartUrl = $this->getAddToCartUrl($product);

        return [
            'action' => $addToCartUrl,
            'data' => [
                'product' => (int)$product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($addToCartUrl),
                'return_url' => $this->redirect->getRefererUrl()
            ]
        ];
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getProduct($sku){
        try {
            return $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getUrlProduct($productId){
        $parentIds = $this->configurable->getParentIdsByChild($productId);

        if (!empty($parentIds)) {
            $productId = $parentIds[0];
        }
        return $this->productRepository->getById($productId,false,$this->getCurrentStoreId())->getUrlInStore();
    }

}
