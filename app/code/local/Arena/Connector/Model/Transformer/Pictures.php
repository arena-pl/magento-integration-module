<?php

class Arena_Connector_Model_Transformer_Pictures extends Arena_Connector_Model_Transformer_Abstract
{
    /**
     * @param $model Mage_Catalog_Model_Product
     *
     * @return array
     */
    public function transform($model)
    {
        $thumbUrl = Mage::getResourceModel('catalog/product')->getAttributeRawValue($model->getId(), 'thumbnail', $model->getStoreId());
        if ($model->getMediaGalleryImages()) {
            $galleryData = $model->getMediaGalleryImages()->toArray();
            $readyPic = array();
            $i = 1;
            foreach ($galleryData['items'] as $pic) {
                if (!in_array($pic['url'], $readyPic)) {
                    if ($thumbUrl == $pic['file']) {
                        $pic['position'] = '00';
                    }
                    $readyPic[$pic['position'].'_'.$i++] = $pic['url'];
                }
            }
            ksort($readyPic);
            $readyPic = array_values($readyPic);
            foreach ($readyPic as $i => $v) {
                $readyPic[$i] = array('url' => $v);
            }
        }
        if ((false === isset($readyPic) || 0 === count($readyPic)) && 'configurable' === $model->getTypeId()) {
            $children = $this->getChildren($model);
            if (count($children) > 0) {
                $childPic = $this->transform($children[0]);
                $readyPic = $childPic['product_pictures']['pictures'];
            }
        }

        return array(
            'product_pictures' => array(
                'id' => $model->getId(),
                'overwrite' => 'true',
                'pictures' => $readyPic,
            ),
        );
    }
}
