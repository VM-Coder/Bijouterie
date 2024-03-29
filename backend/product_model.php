<?php
    require_once 'abstract_model.php';
    require_once 'connection.php';

    final class ProductModel extends AbstractModel {
        protected static $table_name = 'public.product';
        public static function get($id){
            global $user_connect;

            $result = static::select('public.product', $user_connect, [
                'public.product.id', 
                'type', 
                'public.product.name', 
                'manufacturer', 
                'price', 
                'photo', 
                'gender', 
                'size', 
                'ARRAY(SELECT DISTINCT unnest(ARRAY_AGG(public.material.name))) AS materials',
                'ARRAY(SELECT DISTINCT unnest(ARRAY_AGG(public.stone.name))) AS stones'
            ], [
                'public.product.id = ' . $id
            ], [
                ['public.product_material', 'LEFT', 'public.product.id', 'public.product_material.product_id'],
                ['public.material', 'LEFT', 'material_id', 'public.material.id'],
                ['public.product_stone', 'LEFT', 'public.product.id', 'public.product_stone.product_id'],
                ['public.stone', 'LEFT', 'stone_id', 'public.stone.id']
            ], 'public.product.id');

            if (!$result)
                throw new Exception('Ошибка запроса');

            return Database::fetch_all($result);
        }
        public static function filter($type, $min_price, $max_price, $gender, $size, $stones, $materials){
            global $user_connect;

            $result = static::select('public.product', $user_connect, [
                'public.product.id', 
                'type', 
                'public.product.name', 
                'manufacturer', 
                'price', 
                'photo', 
                'gender', 
                'size', 
                'ARRAY(SELECT DISTINCT unnest(ARRAY_AGG(public.material.name))) AS materials',
                'ARRAY(SELECT DISTINCT unnest(ARRAY_AGG(public.stone.name))) AS stones'
            ], [
                $type != '' ? 'type = \'' . $type . '\'' : NULL,
                'price::numeric::float BETWEEN ' . $min_price . ' AND ' . $max_price,
                $gender != '' ? 'gender = \'' . $gender . '\'' : NULL,
                $size != '' ? 'ARRAY[size]::numeric[] && ARRAY[' . $size . ']' : NULL
            ], [
                ['public.product_material', 'LEFT', 'public.product.id', 'public.product_material.product_id'],
                ['public.material', 'LEFT', 'material_id', 'public.material.id'],
                ['public.product_stone', 'LEFT', 'public.product.id', 'public.product_stone.product_id'],
                ['public.stone', 'LEFT', 'stone_id', 'public.stone.id']
            ], 'public.product.id', [
                $stones != '' ? 'ARRAY[\'' . str_replace(',', '\',\'', $stones) . '\']::character varying[] && (ARRAY_AGG(public.stone.name))' : NULL,
                $materials != '' ? 'ARRAY[\'' . str_replace(',', '\',\'', $materials) . '\']::character varying[] && (ARRAY_AGG(public.material.name))' : NULL
                // (sizeof($stones) != 1 || $stones[0] != '') ? 'ARRAY[\'' . implode('\', \'', $stones) . '\']::character varying[] && (ARRAY_AGG(public.stone.name))' : NULL,
                // (sizeof($materials) != 1 || $materials[0] != '') ? 'ARRAY[\'' . implode('\', \'', $materials) . '\']::character varying[] && (ARRAY_AGG(public.material.name))' : NULL
            ], 'public.product.id', 30);

            if (!$result)
                throw new Exception('Ошибка запроса');

            return Database::fetch_all($result);
        }

        public static function toJSON(){
            global $admin_connect; 

            $result = static::select('public.product', $admin_connect, [
                'id', 
                'type', 
                'name', 
                'manufacturer', 
                'price', 
                'photo', 
                'gender', 
                'size', 
            ]);

            if (!$result)
                throw new Exception('Ошибка запроса');

            return Database::fetch_all($result);
        }

        public static function fromJSON(){
            global $admin_connect;

            $data = json_decode(file_get_contents($_FILES['user']['tmp_name']));

            $query = 'INSERT INTO public.order (user_id, date, product_id, amount) VALUES ';

            foreach ($data as $record){
                $query .= '(' . $record->user_id . ', \'' . $record->date . '\', ' . $record->product_id . ', ' . '\'' . $record->amount . '\'),';
            }

            $query = substr($query, 0, -1);

            $result = Database::query($admin_connect, $query);
               
            if (!$result)
                throw new Exception('Ошибка запроса');

            return Database::fetch_all($result);
        }
    }
