<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>Routing System</title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" />

    <!--ICON-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
    
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    
    <style>
        #map { 
            height: 100%; 
            width: 100%; 
            position: relative;
        }
        
        .timeline{
            position: absolute;
            bottom:0;
            width: 100%;
            background-color: #E8E8E8;
            color:gray
        }
        
        .timeline-details{
            background-color: #A9A9A9; 
            bottom:0;
            width: 100%;
            color:#FFF;
            padding: 15px 0;
        }
        
        
        .bing-details{
            background-color: #FFF; 
            bottom:0;
            width: 100%;
            height: 50px;
        }

        
        #schedule_res, #time_res, #distance_res{
            color:#F0E68C;
        }
        
        @media only screen and (max-width: 760px) {
            .timeline-details{
                height: 180px;
            }
        }
        
        @media screen and (max-width: 850px) and (min-width: 760px){
            .timeline-details{
                height: 80px;
            }
        }
    </style>
</head>

<body>
    
    <div class="wrapper" >
        
        
        <div class="main-panel2">
            
            <div id="map"></div>
                
            <div data-grid="col-12" class="timeline">
                    <div class="row" style="margin:0">
                        <div id="timeline" class="container-fluid">Route Timeline</div>
                        
                        <div class="container-fluid timeline-details">
                            <div class="row">
                                <div class="col-md-2">
                                    <label>Total Scehedule: <label id="schedule_res"></label></label>
                                </div>
                                <div class="col-md-3">
                                    <label>Total Completion Time:<label id="time_res">127 Minutes</label></label> <!--127 Minutes, examples-->
                                </div>
                                <div class="col-md-3">
                                    <label>Total Distance :<label id="distance_res"></label></label> 
                                </div>
                                <div class="col-6 col-md-2">
                                    <button type="submit" name="btnReoptimize" id="btnReoptimize" class="btn btn-info btn-fill btn-block">Reoptimize</button>
                                </div>
                                <div class="col-6 col-md-2">
                                    <button type="submit" name="btnDispatch" id="btnDispatch" class="btn btn-success btn-fill btn-block">Dispatch to Driver</button> <!--Dont need to implement-->
                                </div>
                            </div>
                        </div>
                        
                        <div class="bing-details"></div>
                    </div>
            </div>
            
        </div> <!-- END MAIN PANEL-->
        
    </div> <!-- END WRAPPER-->


    
</body>
<!-- Reference to the Bing Maps SDK -->
<script type='text/javascript'
            src='http://www.bing.com/api/maps/mapcontrol?callback=GetMap' async defer></script>
    
<script type='text/javascript'>
    
var map, requestStopLayer, resultStopLayer, routeLayer, isCompleted = false, pan = false, mio, stop_mio, seconds, scenario, infobox; 
var colors = ['#008272', '#0078d4', '#00bcf2 ','#00B294', 'Chartreuse', 'LightSalmon', 'LightSkyBlue', 'Sienna',  'DarkKhaki', 'cyan' ],attempt = 1;
//genetic algorithm datas
var driver_info,stop_info,genes,scores,gene_number=200,generation_number=200;
var global_flag=false;


function GetMap() {
    map = new Microsoft.Maps.Map('#map', {
        credentials: 'BING-MAP-KEYS-HERE'
    });

    //Request the user's location
    navigator.geolocation.getCurrentPosition(function (position) {
        var loc = new Microsoft.Maps.Location(
            position.coords.latitude,
            position.coords.longitude);

        //Center the map on the user's location.
        map.setView({ center: loc, zoom: 10 });
    });
    
    
    requestStopLayer = new Microsoft.Maps.Layer();
    resultStopLayer = new Microsoft.Maps.Layer();

    routeLayer = new Microsoft.Maps.Layer();
    map.layers.insert(requestStopLayer);
    map.layers.insert(resultStopLayer);
    map.layers.insert(routeLayer);

    infobox = new Microsoft.Maps.Infobox(new Microsoft.Maps.Location(0.0, 0.0), { visible: false });
    infobox.setMap(map);
    
    //get the data once the map is loading
    loadDriver();
    loadStop();
}

//function to load the driver json file
function loadDriver(){
    $.ajax({
        url: "driver.json",
        dataType: "json",
        mimeType: "textPlain"
        })          
        .done(function(data){
            //update driver_info
            driver_info=[];
            $.each(data, function (i, agent){
                driver_info.push(agent);
            });
            renderDriverOnMap(data); 
    });
}
    
//function to load the stop json file
function loadStop(){
    $.ajax({
        url: "stop.json",
        dataType: "json",
        mimeType: "textPlain"
        })          
        .done(function(data){
            //update driver_info
            stop_info=[];
            $.each(data, function (i, agent){
                stop_info.push(agent);
            });
            renderStopOnMap(data);  
    });
}

//function to plot the driver location on the map
function renderDriverOnMap(mio) {
    var locations = [];
    routeLayer.clear();
    
    $.each(mio, function (i, agent) {
        var name = agent.driver_name;

        var pushpin0 = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(agent.start_latitude, agent.start_longitude), { title: name , color: 'green' });
        Microsoft.Maps.Events.addHandler(pushpin0, 'click', function (e) {
            infobox.setOptions({
                location: e.target.getLocation(),
                offset: new Microsoft.Maps.Point(0, 10),
                title: 'Starting Point: ' + name,
                description: agent.start_address,
                visible: true,
                showPointer: true,
                maxHeight: 175,
                maxWidth: 300
            });
        });
        requestStopLayer.add(pushpin0);
        locations.push(pushpin0.getLocation());
        
        var pushpin1 = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(agent.end_latitude, agent.end_longitude), { title: name , color: 'red' });
        Microsoft.Maps.Events.addHandler(pushpin1, 'click', function (e) {
            infobox.setOptions({
                location: e.target.getLocation(),
                offset: new Microsoft.Maps.Point(0, 10),
                title: 'Ending Point: ' + name,
                description: agent.end_address,
                visible: true,
                showPointer: true,
                maxHeight: 175,
                maxWidth: 300
            });
        });
        requestStopLayer.add(pushpin1);
        locations.push(pushpin1.getLocation());


    });

    map.setView({ bounds: Microsoft.Maps.LocationRect.fromLocations(locations) });
}
    
//function to plot all the stops on the map
function renderStopOnMap(stop_mio){
    
    var locations = [];
    routeLayer.clear();
    
    $.each(stop_mio, function (i, item) {
        var iconsvg = '<svg xmlns = "http://www.w3.org/2000/svg" width="40" height="40"><g transform="translate(20,20)"><circle cx="0" cy="0" r="10" stroke-width="1" stroke="yellow" fill="{color}" /></g></svg>';
        var pushpin = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(item.stop_latitude, item.stop_longitude), {
            icon: iconsvg,
            text: '' + i,
            textOffset: new Microsoft.Maps.Point(0, 12.5),
            color: 'blue',
            anchor: new Microsoft.Maps.Point(20, 20)
        });
        var description = 'Name:' + item.stop_name + '<br />From:' + item.delivered_time_from + '<br />To:' + item.delivered_time_to + '<br />Delivery Note:' + item.delivery_note1 + '<br />Extra Note:' + item.delivery_note2;

        Microsoft.Maps.Events.addHandler(pushpin, 'click', function (e) {
            infobox.setOptions({
                location: e.target.getLocation(),
                offset: new Microsoft.Maps.Point(0, 10),
                title: 'Stop ' + i,
                description: description,
                visible: true,
                showPointer: true,
                maxHeight: 175,
                maxWidth: 300
            });
        });
        requestStopLayer.add(pushpin);

        locations.push(pushpin.getLocation());

    });
    map.setView({ bounds: Microsoft.Maps.LocationRect.fromLocations(locations) });
}
    
$( "#btnReoptimize" ).click(function(e) {
    e.preventDefault();
    
    optimize();

});

/* 
    Implementation of Genetic Algorithm

    When you load the page, you will see the starting and ending points for two drivers
    and a list of stops (with numbering)

    Once the reoptimize button is clicked,
    genetic algorithm will process, and generate the most-efficient route

    the example of final output,

    driver 1 [starting point of driver1, location1, location2, location9, location4, location3, ending point of driver1]
    driver 2 [starting point of driver2, location5, location11, location6, location8, location7, ending point of driver2]

    Sometimes the data might change (eg 4 drivers with different list of stops), so the algorithm must work for other data as well. The json data provided is just an example. You can try solve with the data provided first.


    *** If the driver does not have an ending point, the route must end with his starting point.
    e.g. 
    driver 2 [starting point of driver2, location5, location11, location6, location8, location7, starting point of driver2]


    If you face any doubts you can message and confirm the functionality with me. :)

    Thank you.


    Any programming languages used to run the genetic algorihtm is fine.
    * as long as it works for this webpage


    **REMEMBER TO GET THE BING MAP KEY TO LOAD THE MAPS

*/   
function optimize(){
    var min_gene=0;	
    initgene();
    console.log("total:");
    for(var pos=0;pos<gene_number;pos++)
        console.log(scores[pos]);
    for(var i=0;i<generation_number;i++){
        console.log("generation"+i);
        kill_gene();
        new_born();
        mutate();
        set_scores();
        min_gene=find_min();
    }

    //test
   global_flag=true;

    //final
    console.log("min_gene"+min_gene);
    var final_paths=gene_score(genes[min_gene],0,true);
    for(var i=0;i<final_paths.length;i++)
        console.log(final_paths[i]);

}

//init gene
function initgene(){
    genes=new Array(gene_number);
    scores=new Array(gene_number);
    //init gene
    for(var pos=0;pos<gene_number;pos++)
        genes[pos]=[];
    //init with random taxi drivers
    for(var pos=0;pos<gene_number;pos++){
        for(var i=0;i<stop_info.length;i++){
            genes[pos].push(Math.floor(Math.random() * driver_info.length));
        }
        scores[pos]=gene_score(genes[pos],pos);
    }
}
//kill_gene
function kill_gene(){
    var sort_scores=[...scores];
    sort_scores.sort();
    var middle_score=sort_scores[gene_number/2];
    console.log("middle score: "+middle_score);
    for(var i=0;i<scores.length;i++)
        if(scores[i]>middle_score){
            genes.splice(i,1);
            scores.splice(i,1);
            i--;   
        }
}
//born new gene
function new_born(){
    var new_gene=[];
    //console.log(genes);
    while (genes.length!=gene_number) {
        new_gene=[];
      //  console.log("new_born");
        var parent_1=Math.floor(Math.random() * genes.length);
        var parent_2=Math.floor(Math.random() * genes.length);

        for( var i=0;i<stop_info.length;i++)
            if(Math.floor(Math.random() *2*scores[parent_1]/scores[parent_2])>0) 
                new_gene.push(genes[parent_1][i]);
            else 
                new_gene.push(genes[parent_2][i]);
      //  console.log("new_born"+new_gene);
        genes.push(new_gene);
    }
}
//mutate
function mutate(){
    var mutate_number=Math.floor(Math.random() * gene_number/20);
    for (var i=0;i<mutate_number;i++){
        var mutate_gene=Math.floor(Math.random() * gene_number);
        var mutate_pos_number=Math.floor(Math.random() * stop_info.length/5);
        //mutate randomv alue
        for(var j=0;j<mutate_pos_number;j++){
            genes[mutate_gene][Math.floor(Math.random() * stop_info.length)]=Math.floor(Math.random() * driver_info.length);
        }
        //swap two value
        for(var j=0;j<mutate_pos_number;j++){
            var a=Math.floor(Math.random() * stop_info.length);
            var b=Math.floor(Math.random() * stop_info.length);
            var temp=genes[mutate_gene][a];
            genes[mutate_gene][a]=genes[mutate_gene][b];
            genes[mutate_gene][b]=temp;
        }
    }
}
//set_scores
function set_scores(){
    for(var pos=0;pos<gene_number;pos++){
       // console.log("set_score"+genes[pos]);
        scores[pos]=gene_score(genes[pos],pos);
    }
}
//find_max
function find_min(){
    console.log("min "+Math.min(...scores));
    return scores.indexOf(Math.min(...scores));
}


//count gene score
function gene_score(gene,pos,final_flag=false){
    var score=0;
    var driver_paths=new Array(driver_info.length);
    var final_paths=new Array(driver_info.length);
    //init driver path
    for(var i=0;i<driver_info.length;i++){
        driver_paths[i]=[];
        final_paths[i]=[];
    }
    
    //init driver path with gene
    for(var i=0;i<gene.length;i++){
       // console.log(gene[i]);
        driver_paths[gene[i]].push(i);
    }

  //  console.log("gene"+pos+" "+gene);
    //sort driver path for minimium path and count score
    for(var i=0;i<driver_info.length;i++){
        score+=nn_algorithm(driver_paths[i],i,final_paths[i]);
        if(final_flag) console.log(final_paths);
       // backtraking(driver_paths[i],i);
    }
    if(final_flag) return final_paths;
    else return score;
}

//find minimium path with nearest neighbour
function nn_algorithm(driver_path,driver_id,cpath){

    var minscore=path_count(driver_id,driver_path,true);
   // console.log("init score: "+minscore);
    var flag=[];
    //init
    var next_pos=0;
    for(var i=0;i<driver_path.length;i++){
        flag.push(false);
    }
    //start searching
    for(var i=0;i<driver_path.length;i++){
        var cvalue=Number.MAX_VALUE;
        for(var j=0;j<driver_path.length;j++){
            if(!flag[j]){
                cpath.push(driver_path[j]);
                //console.log(cpath);

                //test
                if(cvalue>path_count(driver_id,cpath,false)){
                    cvalue=path_count(driver_id,cpath,false);
                    next_pos=j;
                  //  if(global_flag)
                 //   console.log("cvalue : "+cvalue+" next_pos "+driver_path[next_pos]);
                }
                cpath.pop();
            }
        }
        flag[next_pos]=true;
        cpath.push(driver_path[next_pos]);
    }
  //  if(global_flag) console.log("cpath"+cpath);
    //console.log("init score: "+minscore,path_count(driver_id,cpath)+" "+cpath);
    return path_count(driver_id,cpath);
}

//find minimium path with backtracking
function backtraking(driver_path,driver_id){
    //init minscore,minpath,flag
    var minscore=path_count(driver_id,driver_path);
    var flag=[];
    for(var i=0;i<driver_path.length;i++)
        flag.push(false);
    var minpath=[...driver_path];
    var cpath=[];
    
    console.log("startbacktraking:"+minscore);
    //find_minpath
    minscore=find_minpath(driver_path,driver_id,minpath,minscore,cpath,flag);
    console.log("endbacktraking:"+minscore+" "+minpath);
    
    return minscore;
}
function find_minpath(driver_path,driver_id,minpath,minscore,cpath,flag){
    //end
    if(cpath.length==driver_path.length)
        {
            var cscore=path_count(driver_id,cpath);
            if(cscore<minscore){
                minpath=[...cpath];
                minscore=cscore;
         //       console.log("step:"+minscore);
                return minscore;
            }
        }
    //bind brunch
    if (path_count(driver_id,cpath)>minscore)
        return minscore;
    
    //next node
    for(var i=0;i<driver_path.length;i++){
    //    console.log("loop");
   //     console.log(cpath);
        if(flag[i]==false){
            flag[i]=true;
            cpath.push(driver_path[i]);
            minscore=find_minpath(driver_path,driver_id,minpath,minscore,cpath,flag);
            cpath.pop();
            flag[i]=false;
        }
    }
    return minscore;
}

//count path length
function path_count(driver_id,path,final=true){
    //if final path
    if(final){
        var path_length=0;
        //start
        if(path.length==0)
            path_length=100*Math.sqrt( (parseFloat(driver_info[driver_id].end_latitude)- parseFloat(driver_info[driver_id].start_latitude))*(parseFloat(driver_info[driver_id].end_latitude)- parseFloat(driver_info[driver_id].start_latitude))+(parseFloat(driver_info[driver_id].end_longitude)- parseFloat(driver_info[driver_id].start_longitude))*(parseFloat(driver_info[driver_id].end_longitude)- parseFloat(driver_info[driver_id].start_longitude)));

        else if(driver_info[driver_id].end_latitude!=""){
            path_length+=100*Math.sqrt( (parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[0]].stop_latitude))*(parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[0]].stop_latitude))+(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[0]].stop_longitude))*(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[0]].stop_longitude)));
            path_length+=100*Math.sqrt( (parseFloat(driver_info[driver_id].end_latitude)- parseFloat(stop_info[path[path.length-1]].stop_latitude))*(parseFloat(driver_info[driver_id].end_latitude)- parseFloat(stop_info[path[path.length-1]].stop_latitude))+(parseFloat(driver_info[driver_id].end_longitude)- parseFloat(stop_info[path[path.length-1]].stop_longitude))*(parseFloat(driver_info[driver_id].end_longitude)- parseFloat(stop_info[path[path.length-1]].stop_longitude)));
        }
        else{
            path_length+=100*Math.sqrt( (parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[0]].stop_latitude))*(parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[0]].stop_latitude))+(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[0]].stop_longitude))*(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[0]].stop_longitude)));
            path_length+=100*Math.sqrt( (parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[path.length-1]].stop_latitude))*(parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[path.length-1]].stop_latitude))+(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[path.length-1]].stop_longitude))*(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[path.length-1]].stop_longitude)));
        }
        
        for(var i=0;i<path.length-1;i++)
            path_length+=100*Math.sqrt( (parseFloat(stop_info[path[i]].stop_latitude)- parseFloat(stop_info[path[i+1]].stop_latitude))*(parseFloat(stop_info[path[i]].stop_latitude)- parseFloat(stop_info[path[i+1]].stop_latitude))+(parseFloat(stop_info[path[i]].stop_longitude)- parseFloat(stop_info[path[i+1]].stop_longitude))*(parseFloat(stop_info[path[i]].stop_longitude)- parseFloat(stop_info[path[i+1]].stop_longitude)));

        return path_length;
    }
    //search next pos
    else{
      //  console.log(path);
        var path_length=0;
        path_length+=100*Math.sqrt( (parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[0]].stop_latitude))*(parseFloat(driver_info[driver_id].start_latitude)- parseFloat(stop_info[path[0]].stop_latitude))+(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[0]].stop_longitude))*(parseFloat(driver_info[driver_id].start_longitude)- parseFloat(stop_info[path[0]].stop_longitude)));
        for(var i=0;i<path.length-1;i++)
            path_length+=100*Math.sqrt( (parseFloat(stop_info[path[i]].stop_latitude)- parseFloat(stop_info[path[i+1]].stop_latitude))*(parseFloat(stop_info[path[i]].stop_latitude)- parseFloat(stop_info[path[i+1]].stop_latitude))+(parseFloat(stop_info[path[i]].stop_longitude)- parseFloat(stop_info[path[i+1]].stop_longitude))*(parseFloat(stop_info[path[i]].stop_longitude)- parseFloat(stop_info[path[i+1]].stop_longitude)));
        return path_length;
    
    }
}

    
</script>
    


    
</html>
