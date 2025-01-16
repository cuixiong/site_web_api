<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublisherController extends Controller {
    public function alphabeticSearch(Request $request) {
        $keyWord = $request->input('keyWord' , '');

        if(!empty($keyWord)){
            $where = "publisher.company LIKE '%".$keyWord."%'";
        }else{
            $where = '1=1';
        }

        DB::select("SELECT `publisher`.`id`, LEFT(`publisher`.`company`,1) word FROM ".Yii::$app->db->tablePrefix."product_routine `products` LEFT JOIN ".Yii::$app->db->tablePrefix."publisher `publisher` ON products.reseller_id = publisher.id WHERE ".$where." GROUP BY LEFT(`publisher`.`company`,1)")->get()->toArray();

        $publishers = Yii::$app->db->createCommand($sql)->queryAll();

        $sql2 = "SELECT `id` from ".Yii::$app->db->tablePrefix."publisher WHERE `company` REGEXP '^[0-9]'";
        $reseller_ids = Yii::$app->db->createCommand($sql2)->queryColumn();
        $count = Products::find()->where(['in','reseller_id',$reseller_ids])->count('id');
        if($count>0){ // 如果那些以数字开头的出版商名称对应的报告数量大于0，也就是有报告，
            $push_id = "1000";
            array_push($publishers,['id'=>$push_id,'word'=>'0-9']); // 才追加数组，所以在原数组的结尾
        }
        array_unshift($publishers, [
            'id' => '0',
            'word' => 'All',
        ]);
        $data = [];
        foreach($publishers as $publisher){
            if(in_array($publisher['word'],['0','1','2','3','4','5','6','7','8','9'])){
                continue;
            }else{
                $data[] = $publisher;
            }
        }
        if($data){
            return $this->echoData(ApiCode::SUCCESS, $data);
        }else{
            return $this->echoMsg(ApiCode::RETURN_ERROR);
        }
    }

    public function publishers() {
        $get = Yii::$app->request->get();
        $id = $get['id'] ?? null;
        $publisher_id = $get['publisher_id'] ?? null;

        if($id!=null && $id!="0"){
            $where = "`publisher`.`id`=".$id;
        }else if($id=="0"){
            $where = "1=1";
        }else{
            $where = "1=1";
        }
        if(!empty($publisher_id)){
            $andWhere = "id=".$publisher_id;
        }else{
            $andWhere = "1=1";
        }

        if($publisher_id!=null){
            $where = "`publisher`.`id`=".$publisher_id;
            $sql = "SELECT `publisher`.`id`, LEFT(`publisher`.`company`,1) word FROM ".Yii::$app->db->tablePrefix."product_routine `products` LEFT JOIN ".Yii::$app->db->tablePrefix."publisher `publisher` ON products.reseller_id = publisher.id WHERE ".$where." AND `publisher`.`status`=1 GROUP BY LEFT(`publisher`.`company`,1)";
        }else{
            $sql = "SELECT `publisher`.`id`, LEFT(`publisher`.`company`,1) word FROM ".Yii::$app->db->tablePrefix."product_routine `products` LEFT JOIN ".Yii::$app->db->tablePrefix."publisher `publisher` ON products.reseller_id = publisher.id WHERE ".$where." AND `publisher`.`status`=1 GROUP BY LEFT(`publisher`.`company`,1)";
        }
        $publishers = Yii::$app->db->createCommand($sql)->queryAll();
        $data = [];
        foreach($publishers as $publisher){
            if(in_array($publisher['word'],['0','1','2','3','4','5','6','7','8','9']) && empty($publisher_id)){
                continue;
            }else{
                $data[] = $publisher;
            }
        }
        $push_id = 1000;

        if($id==$push_id || (empty($id) && empty($publisher_id))){      // echo 666;exit;
            $sql = "SELECT `id` from ".Yii::$app->db->tablePrefix."publisher WHERE `company` REGEXP '^[0-9]' AND `status`=1";
            $reseller_ids = Yii::$app->db->createCommand($sql)->queryColumn();
            $count = Products::find()->where(['in','reseller_id',$reseller_ids])->count('id');
            if($count>0){ // 如果那些以数字开头的出版商名称对应的报告数量大于0，也就是有报告，
                $push_id = "1000";
                array_push($data,['id'=>$push_id,'word'=>'0-9']); // 才追加数组，所以在原数组的结尾
            }
        }
        foreach($data as $key=>$value){
            if($value['word']=='0-9'){
                $sql = "SELECT `r`.`id` AS `publisher_id`, `r`.`company_logo`, `r`.`company`, `r`.`company_profile` FROM `".Yii::$app->db->tablePrefix."publisher` r RIGHT JOIN `".Yii::$app->db->tablePrefix."product_routine` p ON r.id=p.reseller_id WHERE `r`.`company` regexp '^[0-9]' AND `r`.`status`=1 AND ".$andWhere;//company值以数字开头的出版商 //注意这里的$andWhere可能要制定属于哪张表
                $query = Yii::$app->db->createCommand($sql)->queryAll();
                // echo $query->createCommand()->getRawSql();
            }else{
                if($publisher_id!=null){
                    $query = Publisher::find()->select(['id publisher_id','company_logo','company','company_profile'])
                                      ->where($andWhere)
                                      ->andWhere(['status'=>1])
                                      ->asArray()
                                      ->all();
                }else{
                    $query = Publisher::find()->alias('publisher')
                                      ->rightJoin(['product'=>Products::tableName()],'publisher.id=product.reseller_id')
                                      ->select(['publisher.id publisher_id','publisher.company_logo','publisher.company','publisher.company_profile'])
                                      ->where(['like','publisher.company',$value['word'].'%',false])
                                      ->andWhere($andWhere)
                                      ->andWhere(['publisher.status'=>1])
                                      ->groupBy('product.reseller_id')
                                      ->asArray()
                                      ->all();
                }
            }
            if(!empty($query)){
                $data[$key]['publishers'] = $query;
            }else{
                continue;
            }
        }
        if($data){
            return $this->echoData(ApiCode::SUCCESS, $data);
        }else{
            return $this->echoMsg(ApiCode::SUCCESS);
        }
    }

    public function searchAuto() {
        if(Yii::$app->request->get()){
            $keyWord = Yii::$app->request->get('keyWord',null);
            if($keyWord == null){
                $error = array(
                    'error_code'=>60002,    //提示输入不能为空
                    'message'=>'请输入搜索内容',
                );
                echo json_encode($error);exit;
            }else{
                $keyWords = explode(" ",$keyWord);
                $loop = '';
                for($i=0;$i<count($keyWords);$i++){
                    $loop .= " AND `company` LIKE '%".$keyWords[$i]."%'";
                }
                $sql = "SELECT `r`.`id`,`r`.`company` AS `word` FROM ".Yii::$app->db->tablePrefix."publisher r RIGHT JOIN `".Yii::$app->db->tablePrefix."product_routine` p ON r.id=p.reseller_id WHERE 1=1 ".$loop." GROUP BY `p`.`reseller_id` ORDER BY `r`.`order` desc";
                $searchList = Yii::$app->db->createCommand($sql)->queryAll();

                if($searchList){
                    $data = array(
                        'error_code' => 0,
                        'data'=> $searchList,
                    );
                    echo json_encode($data);exit;
                }else{
                    $error = array(
                        'error_code'=>60001,
                        'data'=> ""
                    );
                    echo json_encode($error);exit;
                }
            }
        }
    }
}
