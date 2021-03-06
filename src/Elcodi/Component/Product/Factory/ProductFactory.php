<?php

/*
 * This file is part of the Elcodi package.
 *
 * Copyright (c) 2014 Elcodi.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author Aldo Chiecchia <zimage@tiscali.it>
 * @author Elcodi Team <tech@elcodi.com>
 */

namespace Elcodi\Component\Product\Factory;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

use Elcodi\Component\Currency\Factory\Abstracts\AbstractPurchasableFactory;
use Elcodi\Component\Product\ElcodiProductStock;
use Elcodi\Component\Product\ElcodiProductTypes;
use Elcodi\Component\Product\Entity\Product;

/**
 * Factory for Product entities
 */
class ProductFactory extends AbstractPurchasableFactory
{
    /**
     * @var boolean
     *
     * Use use stock
     */
    public $useStock;

    /**
     * Set use stock
     *
     * @param boolean $useStock Infinite stock
     *
     * @return $this Self object
     */
    public function setUseStock($useStock)
    {
        $this->useStock = $useStock;

        return $this;
    }

    /**
     * Creates and returns a pristine Product instance
     *
     * Prices are initialized to "zero amount" Money value objects,
     * using injected CurrencyWrapper default Currency
     *
     * @return Product New Product entity
     */
    public function create()
    {
        $zeroPrice = $this->createZeroAmountMoney();

        /**
         * @var Product $product
         */
        $classNamespace = $this->getEntityNamespace();
        $product = new $classNamespace();

        $stock = $this->useStock
            ? 0
            : ElcodiProductStock::INFINITE_STOCK;

        $product
            ->setStock($stock)
            ->setType(ElcodiProductTypes::TYPE_PRODUCT_PHYSICAL)
            ->setShowInHome(true)
            ->setPrice($zeroPrice)
            ->setReducedPrice($zeroPrice)
            ->setAttributes(new ArrayCollection())
            ->setVariants(new ArrayCollection())
            ->setCategories(new ArrayCollection())
            ->setImages(new ArrayCollection())
            ->setWidth(0)
            ->setHeight(0)
            ->setDepth(0)
            ->setWidth(0)
            ->setWeight(0)
            ->setImagesSort('')
            ->setEnabled(true)
            ->setCreatedAt(new DateTime());

        return $product;
    }
}
