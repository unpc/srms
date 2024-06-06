
function extend(obj1,obj2){
    for(var attr in obj2){
        obj1[attr] =  obj2[attr];
    }
}

function close_diolog(){
    $('.dialog').css('display','none');
}

function SetStep(arg){
    this.body=document.body;
    this.opt = {
        show:false,
        content:'.stepCont',
        pageCont:'.pageCont',
        imgWidth:20,
        stepContainerMar:20,
        nextBtn:'.nextBtn',
        prevBtn:'.prevBtn',
        steps:['','',''],
        //pageClass:'',//分页的类或则id
        stepCounts:3,//总共的步骤数
        curStep:1,//当前显示第几页
        animating:false,
        showBtn:true,//是否生成上一步下一步操作按钮
        clickAble:false,//是否可以通过点击进度条的节点操作进度
        Btn_num:false,
        onLoad: function(){

        }
    }
    this.init(arg)
}
//初始化 生成页面的进度条和按钮
SetStep.prototype.init=function(arg){
    var _that=this;
    extend(this.opt,arg);
    this.opt.stepCounts=this.opt.steps.length;
    this.content=$(this.opt.content);
    this.pageCont=this.content.find(this.opt.pageCont)
    var w_con=$(this.content).width()-10;
    var w_li=w_con/this.opt.stepCounts/2;
    var stepContainer=this.content.find('.ystep-container');
    this.stepContainer=stepContainer;
    var stepsHtml=$("<ul class='ystep-container-steps'></ul>");
    var stepDisc = "<li class='ystep-step ystep-step-undone'></li>";
    var stepP=$("<div class='ystep-progress'>"+
                "<p class='ystep-progress-bar'><span class='ystep-progress-highlight' style='width:0%'></span></p>"+
            "</div>");
    var stepDhtml = $("<div class='ystep-desc'></div>")
    var stepDesc = "<span></span>"
    // var stepButtonHtml =$( "<div class='step-button'><button type='button' class='btn btn-default prevBtn' id='prevBtn' class='prevBtn'>上一步</button>"+
    //                     "<button type='button' class='nextBtn middle font-button-save button_login button' id='nextBtn' class='nextBtn'>下一步</button></div>");
    if(this.opt.Btn_num){
        var stepButtonHtml =$("<div class='button_container step-button'>" +
            "<div class='float_right'>" +
            "<input type='button' class='button font-button-default rmargin_16 sm' onclick='close_diolog()' value='取消'>" +
            "<input type='button' class='nextBtn button font-button-save sm' id='nextBtn' value='下一步'>" +
            "</div>"+
            "</div>");
    }else{
        var stepButtonHtml =$("<div class='button_container step-button bmargin_3 clearfix' style='line-height: 30px'>" +
            "<a style='display: block; cursor: pointer; text-decoration: underline' class='prevBtn' id='prevBtn'>返回上一步</a>" +
            "<input type='button' class='nextBtn button font-button-save' style='width: 82px;float: right' id='nextBtn' value='下一步'>" +
            "</div>");
    }



    //需要删除
    stepP.css('width',w_li*2*(this.opt.stepCounts));
    stepP.find('.ystep-progress-bar').css('width',w_li*2*(this.opt.stepCounts));

    for(var i=0;i<this.opt.stepCounts;i++){
        if(i==0){
            var _s=$(stepDisc).addClass('gray').css('fontSize','16px').html('①');
            var _d=$(stepDesc).text(this.opt.steps[i]);
        }else if(i==1){
            var _s=$(stepDisc).addClass('gray').css('fontSize','16px').html('②');
            var _d=$(stepDesc).text(this.opt.steps[i]);
        }else {
            var _s=$(stepDisc).addClass('gray').css('fontSize','16px').html('③');
            var _d=$(stepDesc).text(this.opt.steps[i]);
        }
        stepsHtml.append(_s);
        stepDhtml.append(_d);
    }

    if (this.opt.Btn_num){
        stepsHtml.find('li').css('width','17px').css('marginRight',w_con/(this.opt.stepCounts-1)-22)
    }else{
        stepsHtml.find('li').css('width','17px').css('marginRight',w_con/(this.opt.stepCounts-1)-37)
    }

    stepDhtml.find('span').css('width',w_con/(this.opt.stepCounts-1))

    stepContainer.append(stepsHtml).append(stepP).append(stepDhtml);

    // stepContainer.css('left',(w_con-stepP.width()-this.opt.imgWidth-10-this.opt.stepContainerMar*2)/2)


    this.content.css('overflow','hidden');
    this.setProgress(this.stepContainer,this.opt.curStep,this.opt.stepCounts)
    //判断参数 是否显示按钮 并绑定点击事件
    if(this.opt.showBtn){
        this.content.append(stepButtonHtml)
        this.prevBtn=this.content.find(this.opt.prevBtn)
        this.nextBtn=this.content.find(this.opt.nextBtn)
        this.prevBtn.on('click',function(){
            // if($(this).hasClass('handleAble')){
            if($(_that).attr('disabled')||_that.opt.animating){
                return false;
            }else{
                _that.opt.animating=true;
                _that.opt.curStep--;
                _that.setProgress(_that.stepContainer,_that.opt.curStep,_that.opt.stepCounts)
            }
        })
        this.nextBtn.on('click',function(){
            // if($(this).hasClass('handleAble')){
            if($(_that).attr('disabled')||_that.opt.animating){
                return false;
            }else{
                //检测当前页卡的表单值
                var curstep = $(this).attr('data-curstep');
                if (curstep != 3 && !$('.pageCont').attr('data-ignore-ajax-check')){
                    check_register_values(curstep, _that);
                }else {
                    _that.opt.animating=true;
                    _that.opt.curStep++;
                    _that.setProgress(_that.stepContainer,_that.opt.curStep,_that.opt.stepCounts);
                }
            }
        })
    }
    //判断时候可点击进度条 并绑定点击事件
    if(this.opt.clickAble){
        stepsHtml.find('li').on('click',function(){
            _that.opt.curStep=$(this).index()+1;
            _that.setProgress(_that.stepContainer,_that.opt.curStep,_that.opt.stepCounts)
        })
    }
    $(window).resize(function(){
        var w_con=$(_that.content).width()-10;
        var w_li=w_con/_that.opt.stepCounts/2;
        stepP.css('width',w_li*2*(this.opt.stepCounts));
        stepP.find('.ystep-progress-bar').css('width',w_li*2*(this.opt.stepCounts))
        // stepContainer.css('left',(w_con-stepP.width()-_that.opt.imgWidth-10-_that.opt.stepContainerMar*2)/2)
    })
}
//设置进度条
SetStep.prototype.setProgress=function(n,curIndex,stepsLen){
      var _that=this;
        //获取当前容器下所有的步骤
        var $steps = $(n).find("li");
        var $desc = $(n).find(".ystep-desc span");
        var $progress =$(n).find(".ystep-progress-highlight");
        //判断当前步骤是否在范围内
        if(1<=curIndex && curIndex<=$steps.length){
          //更新进度
          var scale = "%";
          scale = Math.round((curIndex-1)*100/($steps.length-1))+scale;
          $progress.animate({
            width: scale
          },{
            speed: 1000,
            done: function() {
                //描述节点
                $desc.each(function(i,des){
                    var _$des = $(des);
                    var _i = i+1;
                    if(_i < curIndex){
                        _$des.attr("class","ystep-step-done blue");
                    }else if(_i === curIndex){
                        _$des.attr("class","ystep-step-active blue");
                    }else if(_i > curIndex){
                        _$des.attr("class","ystep-step-undone gray");
                    }
                })
              //移动节点
              $steps.each(function(j,m){
                var _$m = $(m);
                var _j = j+1;
                var step_icon;
                if (_j == 1){
                    step_icon = '①';
                }else if (_j == 2){
                    step_icon = '②';
                }else {
                    step_icon = '③';
                }
                if(_j < curIndex){
                  _$m.attr("class","ystep-step-done icon-selected blue");
                  _$m.html('');
                }else if(_j === curIndex){
                  _$m.attr("class","ystep-step-active blue");
                  _$m.html(step_icon);
                }else if(_j > curIndex){
                  _$m.attr("class","ystep-step-undone gray");
                  _$m.html(step_icon);
                }
              })
              if(_that.opt.showBtn){
                  if(curIndex==1){
                      _that.prevBtn.css('display','none')
                      // _that.nextBtn.removeAttr('disabled')
                  }else if(curIndex==stepsLen){
                      _that.prevBtn.css('display','')
                      // _that.nextBtn.attr('disabled','true')
                  }else if(1<curIndex<stepsLen){
                      _that.prevBtn.css('display','')
                      // _that.nextBtn.removeAttr('disabled')
                  }
              }
               _that.checkPage(_that.pageCont,_that.opt.curStep,_that.opt.stepCounts)
               _that.opt.animating=false;
            }
          });  
        }else{
            return false;
        }
}
//改变 分页显示
SetStep.prototype.checkPage=function(pageCont,curStep,steps){
    for(var i = 1; i <= steps; i++){
        if(this.opt.Btn_num){
            if (curStep === steps){
                $('.step-button').css('display','none');
            }else{
                $('.step-button').css('display','block');
            }
        }else{
            if (curStep === steps){
                $('.step-button #nextBtn').attr('name','submit').attr('type','submit').attr('data-curstep',curStep).val('提交');
            }else{
                $('.step-button #nextBtn').removeAttr('name').attr('type','button').attr('data-curstep',curStep).val('下一步');
            }
        }

        if(i === curStep){
          pageCont.find('#page'+i).css("display","block");
        }else{
          pageCont.find('#page'+i).css("display","none");
        }
    }
}