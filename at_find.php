<?php

function at_find($key) {
    require_once('at_config.php');
    
    $db = mysqli_connect(SERVERNAME, USERNAME, PASSWORD, DATABASE);
    if(!$db)
        return array('success'=>-1, 'message'=>'Database connexion error');
    mysqli_set_charset($db,"utf8");
    $key = mysqli_real_escape_string($db, $key);
    $string_query = 
    "   (SELECT id, image_id, name, wilaya, description, 'Ville' as 'type'
        FROM towns
        WHERE name LIKE '%$key%' OR wilaya LIKE '%$key%')
        UNION ALL
        (SELECT points.id, points.image_id, points.name, towns.wilaya, points.description, type
        FROM points, towns
        WHERE towns.id = points.town_id
            AND (points.name LIKE '%$key%' OR towns.wilaya LIKE '%$key%' OR points.type LIKE '%$key%'))
    ";
    $query = mysqli_query($db, $string_query);
    if(!$query)
        return array('success'=>-1, 'message'=>'Database retrieve error');

    $result = array();
    while($row = mysqli_fetch_assoc($query)){
        if( $row['type'] == 'Ville'){
            // get ville rating
            $town_rank = mysqli_query($db, "SELECT  ROUND(SUM(point_ratting) / COUNT(town_id),1) as town_ratting
                From (
                    SELECT point_id, (SUM(rating) / COUNT(rating)) as point_ratting  FROM opinions GROUP BY point_id
                    ) as rating_point,points 
                WHERE rating_point.point_id = points.id AND points.town_id = '$row[id]'");
        // set ville ratting
            if(!$town_rank){
                $row['rating'] = '0.0';
            }else {
                $town_rating = mysqli_fetch_assoc($town_rank)['town_ratting'];
                if ( $town_rating != ''){
                    $row['rating'] = $town_rating;
                }else{
                    $row['rating'] = '0.0';
                }
            }
            }else{
                // get point position longiture / latitude
                $point_position = mysqli_query($db, "SELECT latitude, longitude  FROM points WHERE id = '$row[id]'");
                $point_position = mysqli_fetch_assoc($point_position);

                $row['latitude'] = $point_position['latitude'];
                $row['longitude'] = $point_position['longitude'];
            
                // get point rating
                $point_rank = mysqli_query($db, "SELECT ROUND(SUM(rating) / COUNT(rating),1) as point_rating  FROM opinions WHERE point_id = '$row[id]'");
        
                if(!$point_rank){
                    $row['rating'] = '0.0';
                }else {
                    $point_rating = mysqli_fetch_assoc($point_rank)['point_rating'];
                    if ( $point_rating != ''){
                        $row['rating'] = $point_rating;
                    }else{
                        $row['rating'] = '0.0';
                    }
                }
                

        }
        array_push($result, $row);
    }
        

    mysqli_close($db);

    return array('success'=>1, 'result'=>$result);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['target']) && $_POST['target'] == 'external')
    if(isset($_POST['key']))
        echo json_encode(at_find($_POST['key']));

?>