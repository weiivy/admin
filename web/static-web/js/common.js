document.addEventListener('touchstart',function(){},false);

$(document).on("click",".js-tab-nav",function(){
    var oTab = $(this).closest(".js-tab");
    var index = $(this).index();
    oTab.find(".js-tab-nav").removeClass("active").eq(index).addClass("active");
    oTab.find(".js-tab-body").removeClass("active").eq(index).addClass("active");
})

$(document).on("click",".js-btn-group-radio .item",function(){
   var oRadio = $(this).closest(".js-btn-group-radio");
   oRadio.trigger("change",$(this).data())
    $(this).addClass("active").siblings().removeClass("active");

})


