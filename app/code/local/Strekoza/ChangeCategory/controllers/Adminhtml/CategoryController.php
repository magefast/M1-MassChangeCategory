<?php

class Strekoza_ChangeCategory_Adminhtml_CategoryController extends Mage_Adminhtml_Controller_Action
{

    public function saveAction()
    {
        if (!$category = $this->_initCategory()) {
            return;
        }

        $storeId = $this->getRequest()->getParam('store');
        $refreshTree = 'false';
        if ($data = $this->getRequest()->getPost()) {

            if (!isset($data['target_new_category']) || empty($data['target_new_category'])) {
                $url = $this->getUrl('admin/catalog_category/edit', array('_current' => true, 'id' => $category->getId()));
                $this->getResponse()->setBody(
                    '<script type="text/javascript">updateContent("' . $url . '", {}, ' . $refreshTree . ')</script>'
                );
            }

            try {

                if (isset($data['category_products']) && !$category->getProductsReadonly()) {
                    $products = Mage::helper('core/string')->parseQueryStr($data['category_products']);
                }

                if ($products && count($products) > 0) {
                    $targetCategory = intval($data['target_new_category']);
                    $targetChangeCategory = Mage::getModel('catalog/category')->load($targetCategory);

                    $storeId = (int)$data['store'];
                    $targetChangeCategory->setStoreId($storeId);

                    $this->_saveCategoryProducts($targetChangeCategory, $products);

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('Added products to Category.'));
                }

                $refreshTree = 'true';
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage())->setCategoryData($data);
                $refreshTree = 'false';
            }
        }
        $url = $this->getUrl('adminhtml/catalog_category/edit', array('_current' => true, 'id' => $category->getId()));
        $this->getResponse()->setBody(
            '<script type="text/javascript">parent.updateContent("' . $url . '", {}, ' . $refreshTree . ')</script>'
        );
    }

    public function save2Action()
    {
        if (!$category = $this->_initCategory()) {
            return;
        }

        $storeId = $this->getRequest()->getParam('store');
        $refreshTree = 'false';
        if ($data = $this->getRequest()->getPost()) {

            if (!isset($data['target_new_category']) || empty($data['target_new_category'])) {
                $url = $this->getUrl('admin/catalog_category/edit', array('_current' => true, 'id' => $category->getId()));
                $this->getResponse()->setBody(
                    '<script type="text/javascript">updateContent("' . $url . '", {}, ' . $refreshTree . ')</script>'
                );
            }

            try {

                if (isset($data['category_products']) && !$category->getProductsReadonly()) {
                    $products = Mage::helper('core/string')->parseQueryStr($data['category_products']);
                }

                if ($products && count($products) > 0) {
                    $targetCategory = intval($data['target_new_category']);
                    $targetChangeCategory = Mage::getModel('catalog/category')->load($targetCategory);

                    $storeId = (int)$data['store'];
                    $targetChangeCategory->setStoreId($storeId);

                    $this->_saveCategoryProducts($targetChangeCategory, $products, $category->getId(),true);

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('Added products to Category.'));
                }

                $refreshTree = 'true';
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage())->setCategoryData($data);
                $refreshTree = 'false';
            }
        }

        $url = $this->getUrl('adminhtml/catalog_category/edit', array('_current' => true, 'id' => $category->getId()));
        $this->getResponse()->setBody(
            '<script type="text/javascript">parent.updateContent("' . $url . '", {}, ' . $refreshTree . ')</script>'
        );
    }

    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Initialize requested category and put it into registry.
     * Root category can be returned, if inappropriate store/category is specified
     *
     * @param bool $getRootInstead
     * @return Mage_Catalog_Model_Category
     * @throws Mage_Core_Exception
     */
    protected function _initCategory($getRootInstead = false)
    {
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Categories'))
            ->_title($this->__('Manage Categories'));

        $categoryId = (int)$this->getRequest()->getParam('id', false);
        $storeId = (int)$this->getRequest()->getParam('store');
        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    // load root category instead wrong one
                    if ($getRootInstead) {
                        $category->load($rootId);
                    } else {
                        $this->_redirect('*/*/', array('_current' => true, 'id' => null));
                        return false;
                    }
                }
            }
        }

        if ($activeTabId = (string)$this->getRequest()->getParam('active_tab_id')) {
            Mage::getSingleton('admin/session')->setActiveTabId($activeTabId);
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);
        Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));

        return $category;
    }

    protected function _saveCategoryProducts($category, $products, $currentCategoryId = null, $deleteAction = false)
    {
        $category->setIsChangedProductList(false);
        $categoryId = $category->getId();
        $table = 'catalog_category_product';

        /**
         * old category-product relationships
         */
        $oldProducts = $category->getProductsPosition();
        $newAdded = [];

        foreach ($products as $id => $position) {
            if (!isset($oldProducts[$id])) {
                $oldProducts[$id] = $position;
                $newAdded[$id] = $position;
            }
        }

        $adapter = $this->_getWriteAdapter();

        /**
         * Delete products from category
         */
        if ($deleteAction === true && !empty($products)) {
            $cond = array(
                'product_id IN(?)' => array_keys($products),
                'category_id=?' => intval($currentCategoryId)
            );
            $adapter->delete($table, $cond);
        }

        /**
         * Add products to category
         */
        if (!empty($newAdded)) {
            $data = array();
            foreach ($newAdded as $productId => $position) {
                $data[$productId] = array(
                    'category_id' => (int)$categoryId,
                    'product_id' => (int)$productId,
                    'position' => (int)$position
                );
            }

            $adapter->insertMultiple($table, $data);
        }

        if (!empty($newAdded)) {
            $productIds = array_unique(array_keys($newAdded));
            Mage::dispatchEvent('catalog_category_change_products', array(
                'category' => $category,
                'product_ids' => $productIds
            ));
        }

        if (!empty($newAdded)) {
            $category->setIsChangedProductList(true);

            /**
             * Setting affected products to category for third party engine index refresh
             */
            $productIds = array_keys($newAdded);
            $category->setAffectedProductIds($productIds);
        }
        return $this;
    }

    private function _getWriteAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('write');
    }
}
