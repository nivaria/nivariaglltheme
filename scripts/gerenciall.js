/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$(function(){
    //Node form position
    var jqNodeForm = $("body.page-node #node-form div.node-form");
    if(jqNodeForm.length>0){
        var jqStand = $("body.page-node #node-form div.node-form > .standard");
        var jqRel = $("body.page-node #node-form div.node-form > .relations");
        if(jqStand.length>0 && jqRel.length>0){
            $("<div class='node-form-cols'></div>").prependTo(jqNodeForm);
            jqStand.appendTo(jqNodeForm.find("div.node-form-cols")).css("width","73%").css("float","left");
            jqRel.appendTo(jqNodeForm.find("div.node-form-cols")).css("width","25%").css("float","right");
        }
    }
});

