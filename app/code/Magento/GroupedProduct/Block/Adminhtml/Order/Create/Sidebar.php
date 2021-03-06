<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\GroupedProduct\Block\Adminhtml\Order\Create;

class Sidebar
{
    /**
     * Get item qty
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Sidebar\AbstractSidebar $subject
     * @param callable $proceed
     * @param \Magento\Object $item
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetItemQty(
        \Magento\Sales\Block\Adminhtml\Order\Create\Sidebar\AbstractSidebar $subject,
        \Closure $proceed,
        \Magento\Object $item
    ) {
        if ($item->getProduct()->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            return '';
        }
        return $proceed($item);
    }

    /**
     * Check whether product configuration is required before adding to order
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Sidebar\AbstractSidebar $subject
     * @param callable $proceed
     * @param string $productType
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsConfigurationRequired(
        \Magento\Sales\Block\Adminhtml\Order\Create\Sidebar\AbstractSidebar $subject,
        \Closure $proceed,
        $productType
    ) {
        if ($productType == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            return true;
        }
        return $proceed($productType);
    }
}
