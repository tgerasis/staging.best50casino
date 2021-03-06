function addPlayerReview($this,e,casinoID){
    e.preventDefault();
    var ajax = {"ajax_url": "https://\/www.best50casino.com//\/wp-admin\/admin-ajax.php"};
    // var ajax = {"ajax_url": "https://\/dev.best50casino.com//\/wp-admin\/admin-ajax.php"};
    // var ajax = {"ajax_url": "https://\/localhost/dev.best50casino.com\/wp-admin\/admin-ajax.php"};
    var rating = $($this).parent('form').find('.vote-stats-user').find('b').text();
    rating = rating ? rating : $($this).parent('form').find('.vote-stats-2').text();
    var comment = $($this).parent('form').find('#comment').val();
    var name = $($this).parent('form').find('#nameReview').val();
    var email = $($this).parent('form').find('#emailReview').val();
    var captchResponse = $($this).parent('form').find('#g-recaptcha-response').val();

    if(!rating ){
        $($this).parent('form').find('.star-voting-wrap').append('<b class="text-12" style="color:red;">Please leave a rating.</b>');
        return false;
    }else if(!comment){
        $($this).parent('form').find('#comment').after('<b class="text-12" style="color:red;">Please comment field cannot be empty.</b>');
        return false;
    }else if(!name){
        $($this).parent('form').find('#nameReview').after('<b class="text-12" style="color:red;">Please fill in your name.</b>');
        return false;
    } else if(!email){
        $($this).parent('form').find('#emailReview').after('<b class="text-12" style="color:red;">Email field cannot be empty.</b>');
        return false;
    }
        $.ajax({
            type: 'POST',
            url: ajax.ajax_url,
            data: {
                action: "addPlayerReview",
                casinoID: casinoID,
                rating: rating,
                comment: comment,
                name: name,
                email: email
            },
            dataType: 'html',
            success: function (data) {
                $("#rate-box").html('Thank You, your comment is being reviewed');
                console.log(data);
                refreshComments();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError);
            },
            complete: function () {
            }
        });
    return false;
}

function refreshComments() {
    // $('#comment-section')
}