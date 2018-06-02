$(document).ready(function() {
 	
 	//------------- Maps -------------//

 	/*Marker*/
 	$(function(){
	    $('#map').gmap3({
	      marker:{
	        address: "Haltern am See, Weseler Str. 151"
	      },
	      map:{
	        options:{
	          zoom: 14
	        }
	      }
	    });
	});

	/*Multiple markers*/
	$(function(){
      
        $('#map2').gmap3({
          map:{
            options:{
              center:[46.578498,2.457275],
              zoom: 5
            }
          },
          marker:{
            values:[
              {latLng:[48.8620722, 2.352047], data:"Paris !"},
              {address:"86000 Poitiers, France", data:"Poitiers : great city !"},
              {address:"66000 Perpignan, France", data:"Perpignan ! <br> GO USAP !", options:{icon: "http://maps.google.com/mapfiles/marker_green.png"}}
            ],
            options:{
              draggable: false
            },
            events:{
              mouseover: function(marker, event, context){
                var map = $(this).gmap3("get"),
                  infowindow = $(this).gmap3({get:{name:"infowindow"}});
                if (infowindow){
                  infowindow.open(map, marker);
                  infowindow.setContent(context.data);
                } else {
                  $(this).gmap3({
                    infowindow:{
                      anchor:marker, 
                      options:{content: context.data}
                    }
                  });
                }
              },
              mouseout: function(){
                var infowindow = $(this).gmap3({get:{name:"infowindow"}});
                if (infowindow){
                  infowindow.close();
                }
              }
            }
          }
        });
    });

	/*Geolocation*/
	$(function(){
      
        $('#map3').gmap3({
          getgeoloc:{
            callback : function(latLng){
              if (latLng){
                
                $(this).gmap3({
                  map:{
		            options:{
		              center:latLng,
		              zoom: 15
		            }
		          },
                  marker:{ 
                    latLng:latLng
                  }
                });
              } else {
                $(".localization").hide();
              }
            }
          }
        });
        
      });


	/*Street view panorama*/
	function Panorama(){
        var p,  marker, infowindow, map;
        
        this.setMap = function(obj){
          map = obj;
        }
        
        this.setMarker = function(obj){
          marker = obj;
        }
        
        this.setInfowindow = function(obj){
          infowindow = obj;
        }
        
        this.open = function(){
          infowindow.open(map, marker);
        }
        
        this.run = function(id){
          if (!marker) {
            return;
          }
          p = new google.maps.StreetViewPanorama(
            document.getElementById(id), 
            { navigationControl: true,
              navigationControlOptions: {style: google.maps.NavigationControlStyle.ANDROID},
              enableCloseButton: false,
              addressControl: false,
              linksControl: false
            }
          );
          p.bindTo("position", marker);
          p.setVisible(true);
        }
      };
    
      $(function(){
        
        var points = [
          [-33.88917576169259,151.2442638310547],
          [-33.854398887065486,151.1563732060547],
          [-33.90541911630287,151.0846187504883]
        ],
        map;
        
        $('#map4').gmap3({
          map:{
            options:{
              zoom: 12,
              mapTypeId: google.maps.MapTypeId.ROADMAP,
              streetViewControl: false,
              center: points[0]
            },
            callback: function(aMap){
              map = aMap;
            }
          }
        });
            
        
        $.each(points, function(i, point){
          
          var panorama = new Panorama();
          panorama.setMap(map);
        
          $("#map4").gmap3({
            marker:{
              latLng: point,
              options:{title: "Click to open", draggable: true},
              callback: function(marker){
                panorama.setMarker(marker);
              },
              events:{
                click: function(){
                  panorama.open();
                }
              }
            },
            infowindow:{
              options:{
                content: "<div id='iw"+i+"' class='infow'></div>"
              },
              callback: function(infowindow){
                panorama.setInfowindow(infowindow);
              },
              events:{
                domready: function(){
                  panorama.run("iw"+i);
                }
              }
            }
          });
        });
    });

	/*Polygon*/
	$('#map5').gmap3({
        map:{
          options:{
            center:{lat:24.886436490787712,lng:-70.2685546875}, 
            zoom:4, 
            mapTypeId: google.maps.MapTypeId.TERRAIN 
          }
        },
        polygon: {
          options:{
            strokeColor: "#f40a0a",
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: "#f40a0a",
            fillOpacity: 0.35,
            paths:[
              [25.774252, -80.190262],
              [18.466465, -66.118292],
              [32.321384, -64.75737],
              [25.774252, -80.190262]
            ]
          },
          onces:{
            click: function(polygon, event){
              var vertices = polygon.getPath(),
                contentString = 'Bermuda Triangle Polygon</br>Clicked Location: ' + event.latLng.lat() + ',' + event.latLng.lng() + '</br>';
              
              for(var i=0; i<vertices.length; i++){
                var xy = vertices.getAt(i);
                contentString += '<br>Coordinate ' + i + ' : ' + xy.lat() +', ' + xy.lng();
              }
      
              $(this).gmap3({
                infowindow:{
                  options:{
                    content: contentString,
                    position:event.latLng
                  }
                }
              });
            }
          }
        }
      });

	/*Street view*/
	$("#map6").gmap3({
	  map:{
	    options:{
	      zoom: 18,
	      center: new google.maps.LatLng(40.729884, -73.990988)
	    }
	  }
	});

	var map = $("#map6").gmap3("get"),
	panorama = map.getStreetView();
	panorama.setPosition(map.getCenter());
    panorama.setPov({
      heading: 265,
      zoom:1,
      pitch:0
    });
    panorama.setVisible(true);
 	
});