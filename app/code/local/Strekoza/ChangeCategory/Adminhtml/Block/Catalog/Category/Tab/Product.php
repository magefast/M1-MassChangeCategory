<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

class Strekoza_ChangeCategory_Adminhtml_Block_Catalog_Category_Tab_Product extends Mage_Adminhtml_Block_Catalog_Category_Tab_Product
{

    protected function _prepareLayout()
    {
        $this->setChild('change_category_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Change Category'),
                    'onclick' => "categoryChangeMoveSubmit('" . $this->getSaveUrl() . "', true)"
                ))
        );

        $this->setChild('move_category_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Move to Category'),
                    'onclick' => "categoryChangeMoveSubmit('" . $this->getMoveSaveUrl() . "', true)"
                ))
        );

        return parent::_prepareLayout();
    }

    public function getChangeCategoryButtonHtml()
    {
        return $this->getChildHtml('change_category_button');
    }

    public function getMoveCategoryButtonHtml()
    {
        return $this->getChildHtml('move_category_button');
    }

    public function getMainButtonsHtml()
    {
        $html = parent::getMainButtonsHtml();
        if ($html != '') {

            $options = Mage::helper('bannercategoryproductslist')->getCategoryOptionArray();

            $select = '<select name="target_new_category" style="width: 200px;">';
            $select .= '<option>' . $this->__('Please choose target Category') . '</option>';

            foreach ($options as $id => $name) {
                $select .= '<option value="' . $id . '">' . $name . '</option>';
            }

            $select .= '</select>';

            $html = $select . $this->getChangeCategoryButtonHtml() . $this->getMoveCategoryButtonHtml() . $html;
        }

        return $html;
    }

    public function getSaveUrl(array $args = array()): string
    {
        $params = array('_current' => true);
        $params = array_merge($params, $args);

        return $this->getUrl('changecategory/adminhtml_category/save', $params);
    }

    public function getMoveSaveUrl(array $args = array()): string
    {
        $params = array('_current' => true);
        $params = array_merge($params, $args);

        return $this->getUrl('changecategory/adminhtml_category/save2', $params);
    }
}