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

class GetController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth',['except' => ['getProducts']]);
    }

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
        $suppliers = Suppliers::all();
        return response(json_encode($suppliers),200);
    }

    public function getCategories(){
        $category = Category::with('sub_category')->get();
        return response(json_encode($category),200);
    }

    public function getColors(){
        $colors = Colors::all();
        return response(json_encode($colors),200);
    }

    public function getSizes(){
        $sizes = Sizes::all();
        return response(json_encode($sizes),200);
    }

    public function getProducts(){
        $products = Products::with('color','purchase','purchase.supplier')->get();
        return response(json_encode($products),200);
    }

    public function getBuyers(){
        $buyer = Buyers::all();
        return response(json_encode($buyer),200);
    }

    public function getAllPurchases(){
        $purchases = Purchases::with('supplier','product','history')->get();
        return response(json_encode($purchases),200);
    }

    public function getIncompletePurchases(){
        $purchases = Purchases::with('supplier','product','product.categorie','product.sub_categorie')->where('status','0')->get();
        return response(($purchases),200);
    }

    public function getCompletePurchases(){
        $purchases = Purchases::with('supplier','product','product.categorie','product.sub_categorie')->where('status','complete')->get();
        return response(($purchases),200);
    }

    public function getAllSales(){
        $sales = Sales::with('buyer','product')->get();
        return response(json_encode($sales),200);
    }

    public function getIncompleteSales()
    {
        $sales = Sales::with('buyer', 'product')->where('status', ' ')->get();
        return response(json_encode($sales), 200);
    }

    public function getJournal(Request $request){
       $from = date('Y-m-d H:i:s', strtotime($request->date));
       $to = date('Y-m-d H:i:s', strtotime($from . ' +1 day'));
       $journals = Journals::with('ledger')->whereBetween('created_at',[$from,$to])->get();
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
                    $object->date = $item2->updated_at;
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
        $from = $request->from;
        $to = $request->to;
        $ledgers = Ledgers::with(['journal' => function($query) use($from,$to){
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $query->whereBetween('created_at',[$from,$to]);
        }])->get();
        $list = [];
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
                $list[] = $object;
            }
        }
        return $list;
    }

    public function getTimeWisePurchase(Request $request){
        date_default_timezone_set('Asia/Dhaka');
        $time = $request->time;
        if($time == 'today'){
            $hour = 12;
            $to              = strtotime($hour . ':00:00');
            $from          = strtotime('-1 day', $to);
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $purchases = Purchases::with('supplier','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($purchases),201);
        }
        if($time == 'yesterday'){
            $hour = 12;
            $to             = strtotime($hour . ':00:00');
            $to          = strtotime('-1 day', $to);
            $from          = strtotime('-1 day', $to);
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
            $purchases = Purchases::with('supplier','product')->whereBetween('created_at',[$from,$to])->get();
            return response(json_encode($purchases),201);
        }
        elseif ($time == 'week'){
            $to = date("M-d-y", strtotime('last sunday', strtotime('next week', time())));
            $to = date('Y-m-d',strtotime($to));
            $from = strtotime('-7 day', strtotime($to));
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


}
