function runsearch(){
  console.log(jsondata);
  $.ajax({
  url: 'AJAX/search.php',
  type: 'POST',
  data: JSON.stringify({'searchdata': jsondata}),
  success: function(result){
    console.log(result);
  },
  error: function(result, status){
    console.log(result);
  }
});
}
