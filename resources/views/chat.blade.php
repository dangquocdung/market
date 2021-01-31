@extends('bsb.app')
@inject('lang', 'App\Lang')

@section('content')
    <style>
        .container {
    border: 2px solid #dedede;
    background-color: #f1f1f1;
    border-radius: 15px;
    padding: 10px;
    margin: 10px 0;
}

.dot {
  height: 20px;
  width: 20px;
  background-color: #bbb;
  border-radius: 50%;
  display: inline-block;
}

    </style>

    <div class="header">
        <div class="row clearfix">
            <div class="col-md-12">
                <h3 class="">{{$lang->get(292)}}</h3>
            </div>
        </div>
    </div>
    <div class="body">

        <div class="row clearfix">
            <div class="col-md-12">

            <div class="col-md-4">
                <div style="height: 75vh; min-height: 75vh; width: 100%; position:relative;">
                    <div style="max-height:100%; min-height: 75vh; overflow:auto;border:1px solid grey;">
                        @foreach($users as $key => $user)
                                <div id="user{{$user->id}}" class="container" style="width:90%; margin-left: 5%;" onclick="selectUser({{$user->id}})">
                                    <div class="col-md-6" style="margin-bottom: 0px;">
                                        <div class=\"image-cropper\">
                                            @if ($user->image == "")
                                                <img src="img/user.png" width="20px" class='rounded'>
                                            @else
                                                <img src="images/{{$user->image}}" width="20px" class='rounded'>
                                            @endif
                                        </div>
                                    </div>
                                    <div id="user{{$user->id}}msgCountDotAll" class="dot" style="float: right; background-color: green; opacity: 0; ">
                                        <div style="display: table; margin: 0 auto; color: white; vertical-align: middle; text-align: center;" id="user{{$user->id}}msgCountAll" >0</div>
                                    </div>
                                    <div id="user{{$user->id}}msgCountDot" class="dot" style="float: right; background-color: red; opacity: 0; margin-right: 5px;">
                                        <div style="display: table; margin: 0 auto; color: white; vertical-align: middle; text-align: center;" id="user{{$user->id}}msgCount" >0</div>
                                    </div>
                                    <div class="col-md-6" style="margin-bottom: 0px;">
                                    <div aligh="right" style="float: right;">
                                        <p><h4>{{$user->name}}</h4></p>
                                    </div>
                                    </div>
                                </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div style="height: 75vh; min-height: 75vh; width: 100%; position:relative;">
                    <div id="messagesWindow" style="max-height:65vh; min-height: 65vh; overflow:auto;border:1px solid grey;">
                    </div>
                    <div id="sendMsg" style="height: 10vh; border:1px solid grey;" hidden>
                        <div class="col-md-10">
                            <div class="input-group">
                                <div class="form-line">
                                    <input type="text" id="messageText" class="form-control">
                                </div>
                                <p>{{$lang->get(293)}}</p>
                            </div>
                        </div>
                            <div class="col-md-2" style="height: 100%;">
                                <div style="margin-top: 10%;">
                                <button type="button" class="btn btn-default waves-effect" onclick="sendMsg()" >
                                    <img src="img/iconsend.png" width="25px">
                                </button>
                                </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
        </div>

<script>
    var currentId = 0;

    function selectUser(id){
        console.log(id);
        // load messages

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'POST',
            url: '{{ url("getChatMessages") }}',
            data: {
                user_id: id,
            },
            success: function (data){
                console.log(data);
                document.getElementById("user"+id+"msgCountDot").style.opacity = "0";
                document.getElementById("sendMsg").hidden = false;
                document.getElementById("user"+id).style.backgroundColor = "#cbecff";
                if (currentId != 0)
                    document.getElementById("user"+currentId).style.backgroundColor = "#f1f1f1";
                currentId = id;
                drawMsg(data);
            },
            error: function(e) {
                console.log(e);
            }}
        );
    }

    function myGet() {
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'POST',
            url: '{{ url("getChatMessages") }}',
            data: {
                user_id: currentId,
            },
            success: function (data){
                console.log(data);
                if (currentLength != data.messages.length)
                    drawMsg(data);
            },
            error: function(e) {
                console.log(e);
            }}
        );
    }

    setInterval(myGet, 10000); // one time in 10 sec


    function sendMsg(){
        var text = document.getElementById("messageText").value;
        if (text == "")
            return;

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'POST',
            url: '{{ url("chatNewMessage") }}',
            data: {
                user_id: currentId,
                text: text,
            },
            success: function (data){
                console.log(data);
                document.getElementById("messageText").value = "";
                drawMsg(data)
            },
            error: function(e) {
                console.log(e);
            }}
        );
    }

    var currentLength = 0;

    function drawMsg(data, id){
        var last = "";
        var msg = document.getElementById("messagesWindow");
        msg.innerHTML = "";

        currentLength = data.messages.length;
        data.messages.forEach(function(entry){
            var now = entry.created_at.substr(0, 11);
            if (now != last) {
                var div = document.createElement("div");
                div.innerHTML = `
                        <div class="container" style="width:20%; margin-left: 40%; margin-right: 40%;">
                            <div style="text-align: center;">
                                <div class="font-14">`+ now +`</div>
                            </div>
                        </div>
                        `;
                last = now;
                msg.appendChild(div);
            }
            var div = document.createElement("div");
            var date = entry.created_at.substr(11,5);
            if (entry.author == "customer"){
                div.innerHTML = `
                        <div class="container" style="width:60%; margin-left: 5%; margin-right: 35%; ">
                                    <h4>`+ entry.text +`</h4>
                                    <div align="right"><h5>` + date + `</h5></div>
                            </div>
                        `;
            }else{
                div.innerHTML = `
                            <div class="container" style="width:60%; margin-left: 35%; margin-right: 5%; background-color: #cbecff">
                                <div style="float: right;">
                                    <h4>`+ entry.text +`</h4>
                                    <div align="right"><h5>` + date + `</h5></div>
                                </div>
                            </div>
                        `;
            }
            msg.appendChild(div);
        });
        msg.scrollTop = msg.scrollHeight;
    }


</script>


@endsection

@section('content2')

@endsection
