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
 * @category    Magento
 * @package     Magento_ImportExport
 * @subpackage  integration_tests
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Test for customer export model
 */
namespace Magento\ImportExport\Model\Export\Entity\Eav;

class CustomerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\ImportExport\Model\Export\Entity\Eav\Customer
     */
    protected $_model;

    protected function setUp()
    {
        $this->_model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            'Magento\ImportExport\Model\Export\Entity\Eav\Customer'
        );
    }

    /**
     * Test export method
     *
     * @magentoDataFixture Magento/ImportExport/_files/customers.php
     */
    public function testExport()
    {
        $expectedAttributes = array();
        /** @var $collection \Magento\Customer\Model\Resource\Attribute\Collection */
        $collection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            'Magento\Customer\Model\Resource\Attribute\Collection'
        );
        /** @var $attribute \Magento\Customer\Model\Attribute */
        foreach ($collection as $attribute) {
            $expectedAttributes[] = $attribute->getAttributeCode();
        }
        $expectedAttributes = array_diff($expectedAttributes, $this->_model->getDisabledAttributes());

        $this->_model->setWriter(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
                'Magento\ImportExport\Model\Export\Adapter\Csv'
            )
        );
        $data = $this->_model->export();
        $this->assertNotEmpty($data);

        $lines = $this->_csvToArray($data, 'email');

        $this->assertEquals(
            count($expectedAttributes),
            count(array_intersect($expectedAttributes, $lines['header'])),
            'Expected attribute codes were not exported'
        );

        $this->assertNotEmpty($lines['data'], 'No data was exported');

        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var $customers \Magento\Customer\Model\Customer[] */
        $customers = $objectManager->get(
            'Magento\Registry'
        )->registry(
            '_fixture/Magento_ImportExport_Customer_Collection'
        );
        foreach ($customers as $key => $customer) {
            foreach ($expectedAttributes as $code) {
                if (!in_array($code, $this->_model->getDisabledAttributes()) && isset($lines[$key][$code])) {
                    $this->assertEquals(
                        $customer->getData($code),
                        $lines[$key][$code],
                        'Attribute "' . $code . '" is not equal'
                    );
                }
            }
        }
    }

    /**
     * Test entity type code value
     */
    public function testGetEntityTypeCode()
    {
        $this->assertEquals('customer', $this->_model->getEntityTypeCode());
    }

    /**
     * Test type of attribute collection
     */
    public function testGetAttributeCollection()
    {
        $this->assertInstanceOf(
            'Magento\Customer\Model\Resource\Attribute\Collection',
            $this->_model->getAttributeCollection()
        );
    }

    /**
     * Test for method filterAttributeCollection()
     */
    public function testFilterAttributeCollection()
    {
        /** @var $collection \Magento\Customer\Model\Resource\Attribute\Collection */
        $collection = $this->_model->getAttributeCollection();
        $collection = $this->_model->filterAttributeCollection($collection);
        /**
         * Check that disabled attributes is not existed in attribute collection
         */
        $existedAttributes = array();
        /** @var $attribute \Magento\Customer\Model\Attribute */
        foreach ($collection as $attribute) {
            $existedAttributes[] = $attribute->getAttributeCode();
        }
        $disabledAttributes = $this->_model->getDisabledAttributes();
        foreach ($disabledAttributes as $attributeCode) {
            $this->assertNotContains(
                $attributeCode,
                $existedAttributes,
                'Disabled attribute "' . $attributeCode . '" existed in collection'
            );
        }
        /**
         * Check that all overridden attributes were affected during filtering process
         */
        $overriddenAttributes = $this->_model->getOverriddenAttributes();
        /** @var $attribute \Magento\Customer\Model\Attribute */
        foreach ($collection as $attribute) {
            if (isset($overriddenAttributes[$attribute->getAttributeCode()])) {
                foreach ($overriddenAttributes[$attribute->getAttributeCode()] as $propertyKey => $property) {
                    $this->assertEquals(
                        $property,
                        $attribute->getData($propertyKey),
                        'Value of property "' . $propertyKey . '" is not equals'
                    );
                }
            }
        }
    }

    /**
     * Test for method filterEntityCollection()
     *
     * @magentoDataFixture Magento/ImportExport/_files/customers.php
     */
    public function testFilterEntityCollection()
    {
        $createdAtDate = '2038-01-01';

        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        /**
         * Change created_at date of first customer for future filter test.
         */
        $customers = $objectManager->get(
            'Magento\Registry'
        )->registry(
            '_fixture/Magento_ImportExport_Customer_Collection'
        );
        $customers[0]->setCreatedAt($createdAtDate);
        $customers[0]->save();
        /**
         * Change type of created_at attribute. In this case we have possibility to test date rage filter
         */
        $attributeCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            'Magento\Customer\Model\Resource\Attribute\Collection'
        );
        $attributeCollection->addFieldToFilter('attribute_code', 'created_at');
        /** @var $createdAtAttribute \Magento\Customer\Model\Attribute */
        $createdAtAttribute = $attributeCollection->getFirstItem();
        $createdAtAttribute->setBackendType('datetime');
        $createdAtAttribute->save();
        /**
         * Prepare filter.asd
         */
        $parameters = array(
            \Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP => array(
                'email' => 'example.com',
                'created_at' => array($createdAtDate, ''),
                'store_id' => \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                    'Magento\Core\Model\StoreManagerInterface'
                )->getStore()->getId()
            )
        );
        $this->_model->setParameters($parameters);
        /** @var $customers \Magento\Customer\Model\Resource\Customer\Collection */
        $collection = $this->_model->filterEntityCollection(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
                'Magento\Customer\Model\Resource\Customer\Collection'
            )
        );
        $collection->load();

        $this->assertCount(1, $collection);
        $this->assertEquals($customers[0]->getId(), $collection->getFirstItem()->getId());
    }

    /**
     * Export CSV string to array
     *
     * @param string $content
     * @param mixed $entityId
     * @return array
     */
    protected function _csvToArray($content, $entityId = null)
    {
        $data = array('header' => array(), 'data' => array());

        $lines = str_getcsv($content, "\n");
        foreach ($lines as $index => $line) {
            if ($index == 0) {
                $data['header'] = str_getcsv($line);
            } else {
                $row = array_combine($data['header'], str_getcsv($line));
                if (!is_null($entityId) && !empty($row[$entityId])) {
                    $data['data'][$row[$entityId]] = $row;
                } else {
                    $data['data'][] = $row;
                }
            }
        }
        return $data;
    }
}
