<?php namespace Darryldecode\Cart;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/17/2015
 * Time: 11:03 AM
 */

use Darryldecode\Cart\Helpers\Helpers;
use Illuminate\Support\Collection;

class ItemCollection extends Collection {

    /**
     * Sets the config parameters.
     *
     * @var
     */
    protected $config;

    /**
     * ItemCollection constructor.
     * @param array|mixed $items
     * @param $config
     */
    public function __construct($items, $config)
    {
        parent::__construct($items);

        $this->config = $config;
    }

    /**
     * get the sum of price
     *
     * @return mixed|null
     */
    public function getPriceSum()
    {
        return Helpers::formatValue($this->price * $this->quantity, $this->config['format_numbers'], $this->config);

    }

    public function __get($name)
    {
        if( $this->has($name) ) return $this->get($name);
        return null;
    }

    /**
     * check if item has conditions
     *
     * @return bool
     */
    public function hasConditions()
    {
        if( ! isset($this['conditions']) ) return false;
        if( is_array($this['conditions']) )
        {
            return count($this['conditions']) > 0;
        }
        $conditionInstance = "Darryldecode\\Cart\\CartCondition";
        if( $this['conditions'] instanceof $conditionInstance ) return true;

        return false;
    }

    /**
     * functie modificata (vezi functia originala comentata mai jos)
     * get the single price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceWithConditions($formatted = true)
    {
        $price0 = (($this->price0>0 and $this->hasConditions() and is_array($this->conditions)
            and isset($this->conditions[0]) and data_get($this->conditions[0]->getArgs(),"type")=="vat") ? floatval($this->price0) : 0);
        $newPrice = $originalPrice = floatval($price0 ? $price0 : $this->price);

        if( $this->hasConditions() )
        {
            if( is_array($this->conditions) )
            {
                foreach($this->conditions as $k=>$condition)
                {
                    if( $condition->getTarget() === 'item' )
                    {
                        if($k==0 and gv($x=$this->conditions[$k]->getArgs(),"type")=="vat" and $price0>0) continue;  //$newPrice = $price0;  //tva e prima conditie adaugata produsului
                        else $newPrice -= ($originalPrice - $this->conditions[$k]->applyCondition($originalPrice));
                    }
                }
            }
            else
            {
                if( $this['conditions']->getTarget() === 'item' )
                {
                    $newPrice = $this['conditions']->applyCondition($originalPrice);
                }
            }
            //if($price0==80) dd($newPrice);

            $this->price2 = $newPrice;
            return Helpers::formatValue($newPrice, $formatted, $this->config);
        }

        $this->price2 = $originalPrice;
        return Helpers::formatValue($originalPrice, $formatted, $this->config);
    }

    /*
    public function getPriceWithConditions($formatted = true)
    {
        $originalPrice = $this->price;
        $newPrice = 0.00;
        $processed = 0;

        if( $this->hasConditions() )
        {
            if( is_array($this->conditions) )
            {
                foreach($this->conditions as $k=>$condition)
                {
                    if( $condition->getTarget() === 'item' )
                    {
                        ( $processed > 0 ) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
                        $newPrice = $condition->applyCondition($toBeCalculated);

                        $processed++;
                    }
                }
            }
            else
            {
                if( $this['conditions']->getTarget() === 'item' )
                {
                    $newPrice = $this['conditions']->applyCondition($originalPrice);
                }
            }

            return Helpers::formatValue($newPrice, $formatted, $this->config);
        }
        return Helpers::formatValue($originalPrice, $formatted, $this->config);
    }
    */

    /**
     * get the sum of price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceSumWithConditions($formatted = true)
    {
        return Helpers::formatValue($this->getPriceWithConditions(false) * $this->quantity, $formatted, $this->config);
    }
}
