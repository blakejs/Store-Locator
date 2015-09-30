<?php

/*

 Template Name: Locations Page

*/

?>


<?php get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<div class="locations-content">

<script>

var locationsObject = {};

</script>

	<?php $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' ); $url = $thumb['0'];?>

	<section class="locations-search-row cf focus alt" style="background: url('<?php echo $url; ?>'); background-size: cover;">

		<div class="wrap medium-pad">

			<form id="store-search" class="store-search table">

				<span class="copy table-cell"><h1 class="alt">Find a store</h1></span>

					<div class="table-cell">

						<div class="m-all">

							<input type="text" class="filter" id="input-address" placeholder="Address, City, or Zip">

						</div>

						<div class="d-1of2 t-1of2 m-all">

							<select class="filter" id="input-distance">

								<option value="20">Distance</option>

								<option value="5">5 Miles</option>

								<option value="10">10 Miles</option>

								<option value="20">20 Miles</option>

								<option value="50">50 Miles</option>

							</select>

						</div>

						<div class="d-1of2 t-1of2 m-all last-col">

							<input type="submit" class="btn small" id="searchStores" value="Submit">

						</div>

					</div>

				</div>


			</form>

		</div>

	</section>



	<div class="map-wrap" id="map-wrap">

		<div class="map" id="map" style="width: 100%; height: 450px;">



		</div>

	</div>

		<div class="store-list cf">

			<!-- <div class="heading location-block">Store Locator <span class="results-counter"><span id="resultsCount">0</span> Results</span></div> -->

			<table class="locations-table table">
				<thead><tr><th>COMPANY</th><th>ADDRESS</th><th>PHONE</th><th class="distance">DISTANCE</th></tr></thead>
				<tbody class="store-list-wrap" id="store-list-wrap"></tbody>
			</table>

			<?php

				$args = array('post_type'=>'locations', 'posts_per_page'=>-1);

				$the_query = new WP_Query( $args );

				if ( $the_query->have_posts() ): ?>
				<script>
					<?php while ( $the_query->have_posts() ) : $the_query->the_post();

						$location = get_field("address");

						$address = preg_replace( "/\r|\n|\"/", "", $location["address"] );

						$address = str_replace(', United States', "", $address);

						$title = get_the_title();

						$formatTitle = strtolower(str_replace(' ', '_', $title));

					?>

					<?php if(isset($location['lat']) && $location['lat'][0]): ?>

						locationsObject['<?php echo $formatTitle; ?>'] = {

							title: "<?php echo $title; ?>",

							phone: "<?php the_field('phone'); ?>",

							lat: "<?php echo $location['lat'] ?>",

							lng: "<?php echo $location['lng'] ?>",

							address: "<?php echo $address; ?>",

							position: {

								lat:'<?php echo $location['lat'] ?>',

								lng: '<?php echo $location['lng'] ?>'

							}

						};

					<?php endif; ?>

				<?php endwhile;?>
				</script>
		<?php endif; wp_reset_postdata(); ?>

		</div>

	</div>

	<script>

	jQuery(document).ready(function($) {

		$mapWrapper = $('#map-wrap').length > 0 ? $('#map-wrap') : false;



		// $mapWrapper.height($(window).height() * 0.75);



		// INITIALIZE MAP

		var map = new GMaps({

			    div: '#map',

			    lat: 41.850033,

			    lng: -87.6500523,

			    zoom: 4,

			    scrollwheel: false

			    <?php if(wp_is_mobile()): ?>

			    ,draggable: false

			    <?php endif; ?>

			});



		// GEOLOCATE IF AVAILABLE

		if(navigator.geolocation) {

		    navigator.geolocation.getCurrentPosition(function(position) {

				var lat = position.coords.latitude,

					lng = position.coords.longitude;



	    		map.setCenter(lat, lng);

				map.setZoom(10);

				findLocations(lat, lng, 20);

		    });

		} else {

			alert("Your browser does not support geolocation. Please use search below.")

		}



		// CREATE PINS FOR EACH LOCATION

		$.each(locationsObject, function(key, value) {

			var position = value['position'];

			map.addMarker({

				lat: value['lat'],

				lng: value['lng'],

				title: value['title'],

				infoWindow: {

				  content: '<p>'+value['title']+'</p><p>'+value['phone']+'</p>'

				},

				icon: "<?php echo get_stylesheet_directory_uri(); ?>/library/images/mappin.png"

			});

		});



		// SUBMIT ACTION

		var geocoder = new google.maps.Geocoder();

		$('#store-search').on('submit', function(e){

			e.preventDefault();

			var $form = $(this),

				address = $form.find('#input-address').val(),

				maxDistance = $form.find('option:selected').val(),

				maxDistance = 20; //parseInt(maxDistance);



			geocoder.geocode(

				{'address': address},

				function(results, status) {

					//console.log(results);

			      if (status == google.maps.GeocoderStatus.OK) {

			        var lat = results[0].geometry.location.lat(),

						lng = results[0].geometry.location.lng();

					map.setCenter(lat, lng);

					findLocations(lat, lng, maxDistance);



			      } else {

			        console.log("Geocode was not successful for the following reason: " + status);

			      }

			    }

		    );

		});



		// HELPER - FINDS CLOSEST PINS BASED ON DISTANCE AND FILLS MAP & LOCATIONS

		function findLocations(lat, lng, maxDistance){

			var $listWrap = $('#store-list-wrap'),

				resultsCount = 0,

				bounds = new google.maps.LatLngBounds();



			$('#store-list-wrap').empty();



	    	$.each(locationsObject, function(key, value) {

	    		var latlngA = new google.maps.LatLng(lat, lng),

	    			latlngB = new google.maps.LatLng(value['lat'], value['lng']),

	    			distance = google.maps.geometry.spherical.computeDistanceBetween(latlngA, latlngB),

	    			formatDistance = (distance * 0.000621371).toFixed(2); //Converts distance to miles



	    		if(formatDistance < maxDistance){

	    			resultsCount++;

	    			$listWrap.append(

	    				'<tr class="location-block">' +

							'<td class="location-title">' +

								'<span class="name">'+value['title']+'</span>' +

							'</td>' +

							'<td class="location-content">' +

								'<span class="address"><a href="http://maps.google.com/?q='+value['address']+'" target="_blank" title="">'+value['address']+'</a></span>' +

							'</td>' +

							'<td class="location-phone">' +

								'<span class="phone"><a href="tel:'+value['phone']+'"title="">'+value['phone']+'</a></span>' +

							'</td>' +

							'<td class="distance">'+formatDistance+' mi</td>' +

						'</tr>'

    				);

    				bounds.extend(latlngB);

	    		}

	    	});

	    	console.log(resultsCount);

	    	if(resultsCount > 0){

				map.fitBounds(bounds);

			}else{

    			$listWrap.append(

    				'<tr class="location-block">' +

						'<td class="location-title" colspan="3">' +

							'<span class="name">NO RESULTS FOUND. TRY ANOTHER SEARCH.</span>' +

						'</td>' +

					'</tr>'

				);
			}

		}

	});

	</script>

</div>


<?php endwhile; endif; ?>

<?php get_footer(); ?>
