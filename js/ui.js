/*
 * Reference:
 * http://blogs.microsoft.co.il/blogs/linqed/archive/2009/03/04/generating-html-tables-with-jquery.aspx
 * http://www.xml.com/pub/a/2007/10/10/jquery-and-xml.html
*/


$(function(){
		
				
		$("#submit").bind('click',function(event){
			var location = $("#txtLocation").val();
			getMovieData(location);
		});
		$("#txtLocation").focus();
}); //closing $(


function getMovieData(location){
	var movie_list = new Object; 
	var total,rows,cols;
	$.ajax({ /*Fetch the xml containing movie data */
	type:"POST",
	url:"../getmoviedata.php",
	data:"location="+location,
	dataType:"xml",
	success: function(xml){
		    alert('Hey');
			total = $(xml).find('movies').attr('total');
			$(xml).find('movie').each(function(){
				var name_text = $(this).find('name').text();
				var url_text = $(this).find('url').text();
				movie_list[name_text] = url_text;
				/*$('<li></li>').html(name_text + ' (' + url_text + ')').appendTo('#update-target ol');*/
				
			}); //closing xml iteration
			rows=(total%4==0)?total/4:parseInt(total/4)+1;
			//Create an array of all keys
			var arr_keys = new Array(total);
			var ctr = 0;
			for(var key in movie_list){
				arr_keys[ctr] = key;
				ctr++;
			}
			createDynamicTable($("#tbl"),rows,4,total,arr_keys,movie_list);
		} //closing function		
	}); //closing $.ajax

}

function createDynamicTable(tbody,rows,cols,total,arr_keys,movie_list){
	
	
	if(tbody == null || tbody.length < 1) return ;
	var ctr = 0;
	for(var r = 1 ; r <= rows ; r++){
		var trow = $("<tr>");
		for(var c = 1 ; c <= cols ; c++){
			 var cellText=(ctr < total)?movie_list[arr_keys[ctr]]:'';
			 $("<td>").addClass("tableCell")
			 		   .append($('<img>')	
			 				   .attr("src",cellText)
			 			)	   
			 		   .appendTo(trow);
			 ctr++;
		}
		trow.appendTo(tbody);
	}
	
}

