<?php

class API_Inventory_Info extends API_Common
{
    public function get_stocks($start = 0, $step = 100)
    {
        $this->_ready();
        $stocks = Q('stock')->limit($start, $step);
        $info = [];

        if (count($stocks)) {
            foreach ($stocks as $stock) {
                $tag = Q("{$stock} tag")->to_assoc('id', 'name');
                $data = new ArrayIterator([
                    'id' => $stock->id,
                    'ref_no' => $stock->ref_no,
                    'product_name' => $stock->product_name,
                    'manufacturer' => $stock->manufacturer,
                    'catalog_no' => $stock->catalog_no,
                    'vendor' => $stock->vendor,
                    'model' => $stock->model,
                    'spec' => $stock->spec,
                    'unit_price' => $stock->unit_price,
                    'barcode' => $stock->barcode,
                    'quantity' => $stock->quantity,
                    'summation' => $stock->summation,
                    'location' => $stock->location,
                    'status' => $stock->status,
                    'expire_time' => $stock->expire_time,
                    'type' => $stock->type,
                    'note' => $stock->note,
                    'tag' => $tag,
                    'ctime' => $stock->ctime
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }
}
