<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;

use App\Supplier as Suppliers;
use App\Categorie as Category;
use App\Color as Colors;
use App\Size as Sizes;
use App\Product as Products;
use App\Buyer as Buyers;
use App\Purchase as Purchases;
use App\Sale as Sales;
use App\Journal as Journals;
use App\Ledger as Ledgers;
use App\Ledger_categorie as Ledger_category;
use Mockery\CountValidator\Exception;
use App\Role as Roles;
use App\User as Admins;

class GetController extends Controller
{

    public function checkDuplicate(Request $request){
        $tables = ['color' => 'colors' , 'buyer' => 'buyers' , 'supplier' => 'suppliers' , 'size' => 'sizes' , 'product' => 'products' , 'category' => 'categories' , 'sub_category' => 'sub_categories','email' => 'users'];
        $item = $request->item;
        $value = $request->value;
        if($item == 'email'){
            $match = DB::table($tables[$item])->where('email', $value)->first();
        }
        elseif ($item == 'product'){
            $match = DB::table($tables[$item])->where('meta', $value)->first();
        }
        else{
            $match = DB::table($tables[$item])->where('email', $value)->first();
        }
        if($match){
            $response = [
                'status' => 'found'
            ];
            return response(json_encode($response),200);
        }
        else{
            $response = [
                'status' => 'not found'
            ];
            return response(json_encode($response),200);
        }
    }

    public function getSuppliers(){
        $suppliers = Suppliers::with('purchase','purchase.product')->orderBy('company','ASC')->get();
        return response(json_encode($suppliers),200);
    }

    public function getCategories(){
        $category = Category::with(['sub_category' => function ($query){
            return $query->orderBy('name','ASC');
        }])->orderBy('name','ASC')->get();
        return response(json_encode($category),200);
    }

    public function getColors(){
        $colors = Colors::orderBy('name','ASC')->get();
        return response(json_encode($colors),200);
    }

    public function getSizes(){
        $sizes = Sizes::all();
        return response(json_encode($sizes),200);
    }

    public function getProducts(){
        $products = Products::with(['size','color','product_image'])->orderBy('name','ASC')->get();
        $list = [];
        foreach($products as $item){
            $prd = new \stdClass();
            $prd->id = $item->id;
            $prd->name = $item->name;
            $prd->created_at = $item->created_at;
            $prd->meta = $item->meta;
            $prd->stock = $item->stock;
            $prd->doc = $item->file;
            $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
            $list[] = $prd;
        }
        return response(json_encode($list),201);
    }

    public function getIndivisualProduct(Request $request){
        $item = Products::with(['size','color','product_image'])->where('stock','>',0)->orderBy('id','DESC')->find($request->id);
        $list = [];
            $prd = new \stdClass();
            $prd->id = $item->id;
            $prd->name = $item->name;
            $prd->created_at = $item->created_at;
            $prd->meta = $item->meta;
            $prd->stock = $item->stock;
            $prd->initial_stock = $item->initial_stock;
            $prd->doc = $item->file;
            $prd->detail = $item->detail;
            $prd->images = $item->product_image;
            $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
        return response(json_encode($prd),201);
    }

    public function getCategoryWiseProduct(Request $request){
        $category = Category::where('name',$request->category)->first();
        $item = Products::with(['size','color','product_image'])->where([['stock','>',0],['categorie_id','=',$category->id]])->orderBy('id','DESC')->find($request->id);
        $list = [];
        $prd = new \stdClass();
        $prd->id = $item->id;
        $prd->name = $item->name;
        $prd->created_at = $item->created_at;
        $prd->meta = $item->meta;
        $prd->stock = $item->stock;
        $prd->initial_stock = $item->initial_stock;
        $prd->doc = $item->file;
        $prd->detail = $item->detail;
        $prd->images = $item->product_image;
        $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
        return response(json_encode($prd),201);
    }

    public function getSubCategoryWiseProduct(Request $request){
        $products = Products::with(['size','color','product_image'])->where([['stock','>',0],['sub_categorie_id','=',$request->id]])->orderBy('id','DESC')->get();
        $list = [];
        foreach ($products as $item){
            $prd = new \stdClass();
            $prd->id = $item->id;
            $prd->name = $item->name;
            $prd->created_at = $item->created_at;
            $prd->meta = $item->meta;
            $prd->stock = $item->stock;
            $prd->initial_stock = $item->initial_stock;
            $prd->doc = $item->file;
            $prd->detail = $item->detail;
            $prd->images = $item->product_image;
            $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
            $list[] = $prd;
        }
        return response(json_encode($list),201);
    }

    public function getAvailableProducts(){
        $products = Products::with(['size','color','product_image','categorie','sub_categorie','purchase.supplier'])->where('stock','>',0)->orderBy('name','ASC')->get();
        $list = [];
        foreach($products as $item){
            $prd = new \stdClass();
            $prd->id = $item->id;
            $prd->name = $item->name;
            $prd->category = $item->categorie;
            $prd->sub_category = $item->sub_categorie;
            $prd->supplier = $item->purchase->supplier;
            $prd->created_at = $item->created_at;
            $prd->meta = $item->meta;
            $prd->stock = $item->stock;
            $prd->initial_stock = $item->initial_stock;
            $prd->images = $item->product_image;
            $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
            $list[] = $prd;
        }
        return response(json_encode($list),201);
    }

    public function getBuyers(){
        $buyer = Buyers::orderBy('company','ASC')->get();
        return response(json_encode($buyer),200);
    }

    public function getAllPurchases(){
        $purchases = Purchases::with('supplier','product','purchase_historie')->orderBy('id','DESC')->get();
        return response(json_encode($purchases),200);
    }

    public function getIncompletePurchases(){
        $purchases = Purchases::with('supplier','product','product.categorie','product.sub_categorie')->orderBy('id','DESC')->where('status','0')->get();
        return response(($purchases),200);
    }

    public function getExtendedPurchase(){
        $purchases = Purchases::with(['supplier','product','product.categorie','product.sub_categorie','accounts_purchase_historie' => function($query){
            return $query->orderBy('id','DESC');
        }])->orderBy('id','DESC')->where('status','extended')->get();
        return response(json_encode($purchases),200);
    }

    public function getDuePurchase(){
        $purchases = Purchases::with(['supplier','product','product.categorie','product.sub_categorie','accounts_purchase_historie' => function($query){
            return $query->orderBy('id','DESC');
        }])->orderBy('id','DESC')->where('payment_status','due')->get();
        return response(json_encode($purchases),200);
    }

    public function getFullPaidPurchase(){
        $purchases = Purchases::with(['supplier','product','product.categorie','product.sub_categorie','accounts_purchase_historie' => function($query){
            return $query->orderBy('id','DESC');
        }])->orderBy('id','DESC')->where('payment_status','paid')->get();
        return response(json_encode($purchases),200);
    }

    public function getCompletePurchases(){
        $purchases = Purchases::with('supplier','product','product.categorie','product.sub_categorie')->orderBy('id','DESC')->where('status','complete')->get();
        return response(($purchases),200);
    }

    public function getAllSales(){
        $sales = Sales::with('buyer','sales_historie','product')->orderBy('id','DESC')->get();
        return response(json_encode($sales),200);
    }

    public function getIncompleteSales()
    {
        $sales = Sales::with('buyer','sales_historie','product')->where('status', '0')->orderBy('id','DESC')->get();
        return response(json_encode($sales), 200);
    }

    public function getCompleteSales()
    {
        $sales = Sales::with('buyer','sales_historie','product')->where('status', 'complete')->orderBy('id','DESC')->get();
        return response(json_encode($sales), 200);
    }

    public function getDueSale(){
        $sales = Sales::with(['buyer','product','product.categorie','product.sub_categorie','accounts_sale_historie' => function($query){
            return $query->orderBy('id','DESC');
        }])->where('payment_status','due')->orderBy('id','DESC')->get();
        return response(json_encode($sales),200);
    }

    public function getFullPaidSale(){
        $sales = Sales::with(['buyer','product','product.categorie','product.sub_categorie','accounts_sale_historie' => function($query){
            return $query->orderBy('id','DESC');
        }])->where('payment_status','paid')->orderBy('id','DESC')->get();
        return response(json_encode($sales),200);
    }

    public function getExtendedSale(){
        $sales = Sales::with(['buyer','product','product.categorie','product.sub_categorie','accounts_sale_historie' => function($query){
            return $query->orderBy('id','DESC');
        }])->where('status','extended')->orderBy('id','DESC')->get();
        return response(json_encode($sales),200);
    }

    public function getJournal(Request $request){
       $from = date('Y-m-d H:i:s', strtotime($request->date));
       $to = date('Y-m-d H:i:s', strtotime($from . ' +1 day'));
       $journals = Journals::with('ledger')->whereBetween('created_at',[$from,$to])->orderBy('id','DESC')->get();
       return response($journals,200);
    }

    protected function findAccountForLedger($journal,$ledger,$type){
        $ledgers = [];
        foreach ($journal as $item){
            $single_journal = Journals::with('ledger')->find($item->id);
            foreach ($single_journal->ledger as $item2){
                if($item2->id != $ledger->id && $item2->pivot->account_type == $type){
                    $object = new \stdClass();
                    $object->account = $item2->name;
                    $object->value = $item2->pivot->value;
                    $object->date = $item->created_at;
                    $ledgers[] = $object;
                }
            }
        }
        return $ledgers;
    }

    protected function calculateLedgerBalance($ledgers){
        $balance = 0;
        foreach ($ledgers as $item){
            $balance = floatval($item->value)+$balance;
        }
        return $balance;
    }

    public function getLedgers(Request $request){
        try{
            $ledger = $this->prepareLedgers($request->from, $request->to, $request->id);
            return response($ledger,201);
        }catch (Exception $e){
            return response('error',500);
        }
    }

    public function prepareLedgers($from,$to,$id){
        ////cause the timestamp will be y:m:d 00:00:00
        $to = date('Y-m-d H:i:s', strtotime(' +1 day',strtotime($to)));
        $from = date('Y-m-d H:i:s', strtotime($from));

        $targeted_day = date('Y-m-d H:i:s', strtotime($to));

        $ledgers = Ledgers::with(['journal' => function($query) use($from,$to){
            return $query->whereBetween('created_at',[$from,$to]);
        }])->where('id',$id)->get();

        $beginning = date('Y-m-d H:i:s',strtotime('2018-1-1'));
        $opening_balance_ledger = Ledgers::with(['journal' => function($query) use($beginning,$targeted_day){
            $from = date('Y-m-d H:i:s', strtotime($beginning));
            $to = date('Y-m-d H:i:s', strtotime($targeted_day));
            $query->whereBetween('created_at',[$from,$to]);
        }])->where('id',$id)->get();

        $opening_balance = new \stdClass();

        foreach ($opening_balance_ledger as $item){
            if(sizeof($item->journal)>0){
                $creditors = $this->findAccountForLedger($item->journal,$item,'Cr');
                $debitors = $this->findAccountForLedger($item->journal,$item,'Dr');
                $dr_balance = $this->calculateLedgerBalance($creditors);
                $cr_balance = $this->calculateLedgerBalance($debitors);
                $balance = 0;
                $type = 'N/A';

                if($dr_balance>$cr_balance){
                    $balance = $dr_balance-$cr_balance;
                    $type = "Dr";
                }
                elseif($dr_balance < $cr_balance){
                    $balance = $cr_balance-$dr_balance;
                    $type = "Cr";
                }
                $opening_balance->balance = $balance;
                $opening_balance->balance_type = $type;
            }
            else{
                    $opening_balance->balance = 0;
                    $opening_balance->balance_type = 'N/A';
            }

        }

        foreach ($ledgers as $item){
            if(sizeof($item->journal)>0){
                $creditors = $this->findAccountForLedger($item->journal,$item,'Cr');
                $debitors = $this->findAccountForLedger($item->journal,$item,'Dr');
                $object = new \stdClass();
                $object->id = $item->id;
                $object->name = $item->name;
                $object->cr = (object)$creditors;
                $object->dr = (object)$debitors;
                $dr_balance = $this->calculateLedgerBalance($creditors);
                $cr_balance = $this->calculateLedgerBalance($debitors);
                if($opening_balance->balance_type == 'Dr'){
                    $dr_balance = $dr_balance+floatval($opening_balance->balance);
                }else{
                    $cr_balance = $cr_balance+floatval($opening_balance->balance);
                }
                $object->dr_balance = $dr_balance;
                $object->cr_balance = $cr_balance;

                if($dr_balance>$cr_balance){
                    $balance = $dr_balance-$cr_balance;
                    $type = "Dr";
                }
                elseif($dr_balance < $cr_balance){
                    $balance = $cr_balance-$dr_balance;
                    $type = "Cr";
                }
                $object->balance = $balance;
                $object->balance_type = $type;
                $object->opening_balance = $opening_balance->balance;
                $object->opening_balance_type = $opening_balance->balance_type;
            }
            else{
                $creditors = [];
                $debitors = [];
                $object = new \stdClass();
                $object->id = $item->id;
                $object->name = $item->name;
                $object->cr = (object)$creditors;
                $object->dr = (object)$debitors;
                $dr_balance = 0;
                $cr_balance = 0;
                if($opening_balance->balance_type == 'Dr'){
                    $dr_balance = $dr_balance+floatval($opening_balance->balance);
                }else{
                    $cr_balance = $cr_balance+floatval($opening_balance->balance);
                }
                $object->dr_balance = $dr_balance;
                $object->cr_balance = $cr_balance;

                if($dr_balance>$cr_balance){
                    $balance = $dr_balance-$cr_balance;
                    $type = "Dr";
                }
                elseif($dr_balance < $cr_balance){
                    $balance = $cr_balance-$dr_balance;
                    $type = "Cr";
                }
                else{
                    $balance = $cr_balance-$dr_balance;
                    $type = "N/A";
                }
                $object->balance = $balance;
                $object->balance_type = $type;
                $object->opening_balance = $opening_balance->balance;
                $object->opening_balance_type = $opening_balance->balance_type;
            }
        }
        return json_encode($object);
    }

    public function getTimeWisePurchase(Request $request){
        date_default_timezone_set('Asia/Dhaka');
        $time = $request->time;
        if($time == 'today'){
            $hour = 12;
            $from              = strtotime($hour . ':00:00');
            $to          = strtotime('+1 day', $from);
            $from = date('Y-m-d H:i:s', ($from));
            $to = date('Y-m-d H:i:s',($to));
            $purchases = Purchases::with('supplier','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($purchases),201);
        }
        else if($time == 'yesterday'){
            $hour = 12;
            $to             = strtotime($hour . ':00:00');
            $from          = strtotime('-1 day', $to);
            $from = date('Y-m-d H:i:s', ($from));
            $to = date('Y-m-d H:i:s', ($to));
            $purchases = Purchases::with('supplier','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($purchases),201);
        }
        elseif ($time == 'week'){
            $from = date("M-d-y", strtotime('last sunday'));
            $to = date("M-d-y", strtotime('last sunday', strtotime('next week', time())));
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $purchases = Purchases::with('supplier','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($purchases),201);
        }
        elseif ($time == 'month'){
            $from = date('Y-m-01');
            $to  = date('Y-m-t');
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $purchases = Purchases::with('supplier','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($purchases),201);

        }
    }

    public function getTimeWiseSale(Request $request){
        date_default_timezone_set('Asia/Dhaka');
        $time = $request->time;
        if($time == 'today'){
            $hour = 12;
            $from              = strtotime($hour . ':00:00');
            $to          = strtotime('+1 day', $from);
            $from = date('Y-m-d H:i:s', ($from));
            $to = date('Y-m-d H:i:s',($to));
            $sales = Sales::with('buyer','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($sales),201);
        }
        else if($time == 'yesterday'){
            $hour = 12;
            $to             = strtotime($hour . ':00:00');
            $from          = strtotime('-1 day', $to);
            $from = date('Y-m-d H:i:s', ($from));
            $to = date('Y-m-d H:i:s', ($to));
            $sales = Sales::with('buyer','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($sales),201);;
        }
        elseif ($time == 'week'){
            $from = date("M-d-y", strtotime('last sunday'));
            $to = date("M-d-y", strtotime('last sunday', strtotime('next week', time())));
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $sales = Sales::with('buyer','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($sales),201);
        }
        elseif ($time == 'month'){
            $from = date('Y-m-01');
            $to  = date('Y-m-t');
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $sales = Sales::with('buyer','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($sales),201);

        }
    }

    protected function getColorWiseSizeQuantity($colors,$sizes){
        $list = [];
        $ids = [];
        foreach ($colors as $color){
            $counter = 0;
            foreach ($colors as $item){
                if($item->id == $color->id){
                    $counter++;
                }
            }
            if(!(in_array($color->id,$ids))){
                $obj = new \stdClass();
                $obj->id = $color->id;
                $obj->name = $color->name;
                $obj->hex = $color->hex;
                $sizesTobeRemoved = $this->getSizeWiseQuantity($sizes,$counter);
                $obj->sizes = $sizesTobeRemoved;
                $list[] = $obj;
                array_push($ids,$color->id);
                $sizes = $this->removeSizesFromList($sizes,$sizesTobeRemoved);
            }
        }
        return $list;
    }

    protected function removeSizesFromList($list,$romoveSize){
        foreach ($romoveSize as $removeKey =>$value){
            foreach ($list as $itemKey => $item){
                if($item->id == $value->id){
                    unset($list[$itemKey]);
                    break;
                }
            }
        }
        return $list;
    }

    protected function getSizeWiseQuantity($sizes,$limit){
        $list = [];
        $counter = 0;
        foreach ($sizes as $size){
                if($counter < $limit){
                    $obj = new \stdClass();
                    $obj->id = $size->id;
                    $obj->name = $size->name;
                    $quantity = $size->pivot;
                    $obj->quantity = $quantity->quantity;
                    $list[] = $obj;
                    $counter++;
                }
        }
        return $list;
    }

    public function getLedgerCategories(){
        $groups = Ledger_category::get();
        return response(json_encode($groups),201);
    }

    public function getLedgerList(){
        $ledgers = Ledgers::orderBy('name','ASC')->get();
        return response($ledgers,201);
    }

    public function getBankAccountsLedgers(){
        $ledgers = Ledger_category::with(['ledger' => function($query){
            return $query->orderBy('name','ASC');}
        ])->where('name','Bank Account')->first();
        $list = [];
        foreach ($ledgers->ledger as $item){
            $list[] = $item;
        }
        return response(json_encode($list),201);
    }

    public function getPurchaseWiseReport(Request $request){
        try{
            $purchase = Purchases::with(['product','product.color','supplier','product.categorie','product.sub_categorie','product.sale' => function ($query){
                return $query->where('status','complete');
            },
                'product.sale.sales_historie' => function ($query){
                    return $query->orderBy('created_at','DESC');
                }
            ,'product.sale.buyer'])->find($request->id);

            //return $purchase;

            $list_purchase = [];
            $obj = new \stdClass();
            foreach($purchase->product as $item){
                $prd = new \stdClass();
                $prd->id = $item->id;
                $prd->name = $item->name;
                $prd->unit_price = $item->purchase_unit_price;
                $prd->created_at = $item->created_at;
                $prd->meta = $item->meta;
                $prd->stock = $item->stock;
                $prd->initial_stock = $item->initial_stock;
                $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
                $prd->category = $item->categorie;
                $prd->sub_category = $item->sub_categorie;
                $list_purchase[] = $prd;
            }
            $purchase_obj = new \stdClass();
            $purchase_obj->id = $purchase->id;
            $purchase_obj->created_at = $purchase->created_at;
            $purchase_obj->reference = $purchase->reference;
            $purchase_obj->supplier = $purchase->supplier;
            $purchase_obj->transport = $purchase->transport;
            $purchase_obj->vat = $purchase->vat;
            $purchase_obj->discount = $purchase->discount;
            $purchase_obj->other = $purchase->other;
            $purchase_obj->labour = $purchase->labour;
            $purchase_obj->status = $purchase->status;
            $purchase_obj->products = $list_purchase;

            $list_sale = [];

            foreach ($purchase->product as $item){
                if(sizeof($item->sale)>0){
                    foreach ($item->sale as $sale){
                        if($sale->status == 'complete'){
                            $sales_obj = new \stdClass();
                            $sales_obj->id = $sale->id;
                            $sales_obj->reference = $sale->reference;
                            $sales_obj->buyer = $sale->buyer;
                            $history =  $sale->sales_historie;
                            foreach ($history as $his){
                                $sales_obj->product = json_decode($this->processSalesproductObject($his->history,$item->name,$sale->pivot,$item->id));
                                break;
                            }
                            $list_sale[] =$sales_obj;
                        }
                    }
                }
            }

            $p_l = $this->calculateProfitLoss($purchase_obj,$list_sale);
            $obj->purchase = $purchase_obj;
            $obj->sales = $list_sale;
            $obj->pl = json_decode($p_l);

            return response(json_encode($obj),201);

        }catch (Exception $e){
            return response("error",500);
        }
    }

    public function getSupplierWiseReport(Request $request){
        $report = Suppliers::with(['purchase' => function($query){
            return $query->orderBy('id','DESC');
        },'purchase.product'])->where('id',$request->id)->first();
        return response(($report),201);
    }

    protected function processSalesproductObject($history,$name,$pivot,$pid){
        $data = json_decode($history);
        foreach ($data->products as $item){
            if($item->id == $pid){
                $obj = new \stdClass();
                $obj->id = $item->id;
                $obj->name = $name;
                $obj->price = $pivot->price;
                $obj->total_amount = $pivot->total_amount;
                $obj->colors = $item->colors;
                return json_encode($obj);
            }
        }

    }

    protected function calculateProfitLoss($purchase,$sales){
        $pl_ob = new \stdClass();
        $total_purchase_value = 0;
        $total_sale_value = 0;
        $total_stock_value = 0;
        foreach ($purchase->products as $item){
            $total_purchase_value = $total_purchase_value+($item->initial_stock*$item->unit_price);
            $total_stock_value = $total_stock_value+($item->unit_price*$item->stock);
        }

        $total_purchase_value = $total_purchase_value+$purchase->transport+$purchase->labour+$purchase->other+$purchase->discount;
        foreach ($sales as $sale){
            $total_sale_value = $total_sale_value+($sale->product->price * $sale->product->total_amount);
        }
        
        $gross_pl = $total_sale_value-$total_purchase_value;
        $net_pl = $gross_pl+$total_stock_value;

        $pl_ob->gross_pl = $gross_pl;
        $pl_ob->net_pl = $net_pl;
        $pl_ob->stock_value = $total_stock_value;

        return json_encode($pl_ob);


    }

    public function getIndivisualPurchase(Request $request){
        try{
            $purchase = Purchases::with('product','product.color','supplier')->find($request->id);
            $list = [];
            foreach($purchase->product as $item){
                $prd = new \stdClass();
                $prd->id = $item->id;
                $prd->name = $item->name;
                $prd->created_at = $item->created_at;
                $prd->meta = $item->meta;
                $prd->stock = $item->stock;
                $prd->initial_stock = $item->initial_stock;
                $prd->category = $item->categorie;
                $prd->sub_category = $item->sub_categorie;
                $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
                $list[] = $prd;
            }
            $obj = new \stdClass();
            $obj->id = $purchase->id;
            $obj->created_at = $purchase->created_at;
            $obj->reference = $purchase->reference;
            $obj->supplier = $purchase->supplier;
            $obj->transport = $purchase->transport;
            $obj->vat = $purchase->vat;
            $obj->discount = $purchase->discount;
            $obj->other = $purchase->other;
            $obj->labour = $purchase->labour;
            $obj->status = $purchase->status;
            $obj->product = $list;

            return response(json_encode($obj),201);

        }catch (Exception $e){
            return response("error",500);
        }

    }

    public function getIndivisualSale(Request $request){
        try{
            $sale = Sales::with(['sales_historie' => function ($query){
                return $query->orderBy('created_at','DESC')->first();
            },'product','buyer'])->where('id',$request->id)->first();
            $obj = new \stdClass();
            $obj->id = $sale->id;
            $obj->created_at = $sale->created_at;
            $obj->buyer = $sale->buyer;
            $obj->reference = $sale->reference;
            $obj->transport = $sale->transport;
            $obj->vat = $sale->vat;
            $obj->discount = $sale->discount;
            $obj->other = $sale->other;
            $obj->warehouse_id = $sale->warehouse_id;
            $obj->accounts_id = $sale->accounts_id;
            foreach ($sale->sales_historie as $item){

                $obj->history = $this->processHistory(json_decode($item->history));
                return $obj->history;
                break;
            }

            return response(json_encode($obj),200);

        }catch (Exception $e){
            return response("error",500);
        }

    }

    protected function processHistory($history){
        foreach ($history->products as $prd){
            foreach ($prd->colors as $clr){
                foreach ($clr->sizes as $siz){
                    $item = Products::with(['color' => function($query) use($clr){
                        return $query->where('id',$clr->id)->get();
                    }, 'size' => function($query) use($siz){
                        return $query->where('id',$siz->id)->get();
                    }])->where('id',$prd->id)->first();
                    $stock = $item->size[0]->pivot->quantity+$siz->quantity;
                    $siz->stock = $stock;
                }
            }
        }
        return json_encode($history);
    }

    public function getRoles(){
        try{
            $roles = Roles::orderBy('name','ASC')->get();
            return response(json_encode($roles),201);
        }catch (Exception $e){
            return response("error",500);
        }
    }

    public function prepareBalanceSheet($from,$to){
        $ledgers = Ledgers::with(['ledger_categorie' => function($query){
            return $query->where('name', '!=', 'Particular');
        }])->get();
        $balance_sheet = [];
        foreach ($ledgers as $item){
            if(sizeof($item->ledger_categorie)>0){
                $data = json_decode($this->prepareLedgers($from,$to,$item->id));
                $obj = new \stdClass();
                $obj->id = $data->id;
                $obj->name = $data->name;
                $obj->balance = $data->balance;
                $obj->type = $data->balance_type;
                $balance_sheet[] = $obj;
            }
        }
        return $balance_sheet;
    }

    public function getProfitAndLossAccount(Request $request){
        $balance_sheet = $this->prepareBalanceSheet($request->from, $request->to);
        $profit_loss = new \stdClass();
        $expense = new \stdClass();
        $revenue = new \stdClass();

        $total_direct_expense = 0;
        $direct_expenses_accounts = [];
        $direct_revenues_accounts = [];
        $total_direct_revenue = 0;
        foreach ($balance_sheet as $item){
            if($item->name == 'Purchase' || $item->name == 'Transport' || $item->name == 'Other' || $item->name == 'Labour'){
                $obj = new \stdClass();
                $obj->name = $item->name;
                $obj->balance = $item->balance;
                $total_direct_expense = $total_direct_expense+$item->balance;
                $direct_expenses_accounts [] = $obj;
            }
        }
        foreach ($balance_sheet as $item){
            if($item->name == 'Sale'){
                $obj = new \stdClass();
                $obj->name = $item->name;
                $obj->balance = $item->balance;
                $total_direct_revenue = $total_direct_revenue+$item->balance;
                $direct_revenues_accounts[] = $obj;
            }
        }
        $from = date('Y-m-d H:i:s', strtotime($request->from));
        $to = date('Y-m-d H:i:s', strtotime($request->to));

        $purchase = Purchases::with('product')->whereBetween('created_at',[$from,$to])->get();
        $closing_stock = 0;
        foreach ($purchase as $item){
            foreach ($item->product as $product){
                $price = $product->stock * $product->purchase_unit_price;
                $closing_stock = $closing_stock+$price;
            }
        }
        $closing_obj = new \stdClass();
        $closing_obj->name = 'Closing Stock';
        $closing_obj->balance = $closing_stock;
        $total_direct_revenue = $total_direct_revenue+$closing_stock;
        $direct_revenues_accounts[] = $closing_obj;

        if($total_direct_revenue> $total_direct_expense){
            $gross_value = $total_direct_revenue-$total_direct_expense;
            $gross_type = 'profit';
        }
        elseif($total_direct_revenue< $total_direct_expense){
            $gross_value = $total_direct_expense - $total_direct_revenue;
            $gross_type = 'loss';
        }
        else{
            $gross_value = 0;
            $gross_type = 'same';
        }

        $indirect_expenses_accounts = [];
        $total_indirect_expense = 0;

        $total_indirect_revenue = 0;
        $indirect_revenues_accounts = 0;

        $d_ex = new \stdClass();
        $d_ex->accounts = $direct_expenses_accounts;
        $d_ex->total = $total_direct_expense;

        $ind_ex = new \stdClass();
        $ind_ex->accounts = $indirect_expenses_accounts;
        $ind_ex->total = $total_indirect_expense;

        $d_rev = new \stdClass();
        $d_rev->accounts = $direct_revenues_accounts;
        $d_rev->total = $total_direct_revenue;

        $ind_rev = new \stdClass();
        $ind_rev->accounts = $indirect_revenues_accounts;
        $ind_rev->total = $total_indirect_revenue;


        if($gross_type == 'profit'){
            $total_indirect_revenue = $total_indirect_revenue+$gross_value;
        }
        else{
            $total_indirect_expense = $total_indirect_expense+$gross_value;
        }

        if($total_indirect_expense>$total_indirect_revenue){
            $net_type = 'loss';
            $net_value = $total_indirect_revenue+$gross_value;
        }
        elseif($total_indirect_expense<$total_indirect_revenue){
            $net_type = 'profit';
            $net_value = $total_indirect_expense+$gross_value;
        }else{
            $net_type = 'same';
            $net_value = 0;
        }

        $expense->direct = $d_ex;
        $expense->indirect = $ind_ex;
        $revenue->direct = $d_rev;
        $revenue->indirect = $ind_rev;


        $profit_loss->expense = $expense;
        $profit_loss->revenue = $revenue;
        $profit_loss->gross_value = $gross_value;
        $profit_loss->gross_type = $gross_type;
        $profit_loss->net_value = $net_value;
        $profit_loss->net_type = $net_type;


        return json_encode($profit_loss);
    }

    public function getPurchaseHistoryAccounts(Request $request){
        try{
            $history = Purchases::with(['accounts_purchase_historie' => function($query){
                return $query->orderBy('id','DESC')->get();
            }])->find($request->id);
            return response($history,201);
        }catch (Exception $e){
            return response("error",500);
        }

    }

    public function getSaleHistoryAccounts(Request $request){
        try{
            $history = Sales::with(['accounts_purchase_historie' => function($query){
                return $query->orderBy('id','DESC')->get();
            }])->find($request->id);
            return response($history,201);
        }catch (Exception $e){
            return response("error",500);
        }

    }

    public function filterStock(Request $request){
        $products = Products::with(['size','color','product_image','categorie','sub_categorie','purchase.supplier'])->where('stock','>',0)->orderBy('id','DESC')->get();
        $list = [];
        foreach($products as $item){
            $prd = new \stdClass();
            $prd->id = $item->id;
            $prd->name = $item->name;
            $prd->category = $item->categorie;
            $prd->sub_category = $item->sub_categorie;
            $prd->supplier = $item->purchase->supplier;
            $prd->created_at = $item->created_at;
            $prd->meta = $item->meta;
            $prd->stock = $item->stock;
            $prd->initial_stock = $item->initial_stock;
            $prd->images = $item->product_image;
            $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
            $list[] = $prd;
        }
        $new_list = [];
        if ($request->category_id > 0 && $request->sub_category_id > 0 && $request->supplier_id > 0){

            foreach ($list as $item){
                if($item->category->id == $request->category_id && $item->sub_category->id == $request->sub_category_id && $item->supplier->id == $request->supplier_id ){
                    $new_list[] = $item;
                }
            }
        }
        elseif ($request->category_id > 0 && $request->sub_category_id > 0){
            foreach ($list as $item){
                if($item->category->id == $request->category_id && $item->sub_category->id == $request->sub_category_id){
                    $new_list[] = $item;
                }
            }
        }
        elseif ($request->category_id > 0 &&  $request->supplier_id > 0){
            foreach ($list as $item){
                if($item->category->id == $request->category_id && $item->supplier->id == $request->supplier_id){
                    $new_list[] = $item;
                }
            }
        }
        elseif ($request->sub_category_id > 0 && $request->supplier_id > 0){
            foreach ($list as $item){
                if($item->sub_category->id == $request->sub_category_id && $item->supplier->id == $request->supplier_id){
                    $new_list[] = $item;
                }
            }
        }
        elseif ($request->category_id >0){
            foreach ($list as $item){
                if($item->category->id == $request->category_id){
                    $new_list[] = $item;
                }
            }
        }
        elseif ($request->sub_category_id > 0){
            foreach ($list as $item){
                if($item->sub_category->id == $request->sub_category_id){
                    $new_list[] = $item;
                }
            }
        }
        elseif ($request->supplier_id > 0){
            foreach ($list as $item){
                if($item->supplier->id == $request->supplier_id){
                    $new_list[] = $item;
                }
            }
        }
        else{
            foreach ($list as $item){
                $new_list[] = $item;
            }
        }
        return response(json_encode($new_list),201);

    }

    public function getAdmins(){
        try{
            $admins = Admins::orderBy('name','ASC')->get();
            return response(json_encode($admins),201);
        }catch (Exception $e){
            return response('error',500);
        }

    }

    public function getIndivisualAdmin(Request $request){
        try{
            $admin = Admins::find($request->id);
            return response(json_encode($admin),201);
        }catch (Exception $e){
            return response('error',500);
        }
    }

    public function search(Request $request){
        try{
            $products = Products::with(['size','color','product_image','categorie','sub_categorie','purchase.supplier'])->where('name','LIKE','%'.$request->text.'%')->orderBy('name','ASC')->get();
            $list = [];
            foreach($products as $item){
                $prd = new \stdClass();
                $prd->id = $item->id;
                $prd->name = $item->name;
                $prd->category = $item->categorie;
                $prd->sub_category = $item->sub_categorie;
                $prd->supplier = $item->purchase->supplier;
                $prd->created_at = $item->created_at;
                $prd->meta = $item->meta;
                $prd->stock = $item->stock;
                $prd->initial_stock = $item->initial_stock;
                $prd->images = $item->product_image;
                $prd->colors = $this->getColorWiseSizeQuantity($item->color,$item->size);
                $list[] = $prd;
            }
            return response(json_encode($list),201);

        }catch(Exception $e){
            return response('error',500);
        }
    }

}