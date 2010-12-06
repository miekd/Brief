/**
 * @fileoverview Skype Panamericana Chatstyle main JS library
 * @author Margus Holland (margusholland@me.com)
 * @version 1.0
*/

if (typeof SCS == "undefined") {
    var SCS = {};
}

/**
 * Creates ID’s for any item
 * @base SCS
 * @class Construct a new SCS.Identifier object
 * @constructor
 */
SCS.Identifier = function() {    

    /**
     * Create 16 character random string to be used as ID
     * @private
     * @return 16 character long alphanumeric string
     * @type {String}
     */
    var _randomString = function() {
        var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
        var strLength = 16;
        var rndString = "";
        for (var i=0; i< strLength; i++) {
            var rnum = Math.floor(Math.random() * chars.length);
            rndString += chars.substring(rnum, rnum+1);
        }
        return rndString;
    };
    
    /**
     * Add new ID to an element
     * @param {String} id ID to add to HTML markup first item
     * @param {String} html HTML markup
     * @return HTML with with an ID added to the first element
     * @type {String}
     */
    this.addId = function(html, id) {
        if (typeof id == "undefined" || id == "") {
            var id = _randomString();
        }
        $(html).each(function() {
            $html = $(this).attr("id", id);
        });
        return $html.get(0);
    };
};

/**
 * Errorhandler
 * @base SCS
 * @class Construct a new errorhandler object
 * @constructor
 */
SCS.ErrorHandler = function() {
    var _statuses = {
        "200": "OK",
        "510": "Can’t find conversation",
        "511": "Can’t find item",
        "512": "Index must be integer and >= 0",
        "513": "ID empty or undefined",
        "514": "Unknown value",
        "515": "Cocoa display controller dC undefined"
    };
    
    /**
     * Return humanreadable status code description for 200, 510, 511, 512, 513, 514, 515
     * @param {int} code Status code
     * @param {fname} fname Name of the function to return as part of the string
     * @return Status string or false if unknown code
     * @type {String}
     */
    this.showError = function(code, fname) {
        var errString = "";
        if (typeof code != "undefined") {
            if (typeof _statuses[code] == "undefined") {
                return false;
            }
            if (typeof fname != "undefined" && fname != "") {
                errString = fname + " : " + "["+code+"] " +  _statuses[code];
            } else {
                errString = "["+code+"] " +  _statuses[code];
            }
            if (typeof debug != "undefined" && debug == true) {
                console.log(errString);
            }
            return errString;
        } else {
            return false;
        }
    };
};

/**
 * Main conversation class
 * @base SCS
 * @class Construct a new SCS.Conversation object
 * @constructor
 */
SCS.Conversation = function() {
    var self = this;
    var _container;
    var _scrolltimer = false;
    
    /**
     * Conversation init
     * @private
     */
    this._init = function() {
        if (_container.length > 0) {
            $(window).scroll(function() {
                if (window.scrollY < 11 && _scrolltimer == false) {
                    dC.scrollWindowToTopEvent();
                    _scrolltimer = true;
                }
                if (window.scrollY > 10) {
                    _scrolltimer = false;
                }
            });
            return SCS.err.showError(200, "_init");
        } else {
            return SCS.err.showError(510, "_init");
        }
    };
    
    /**
     * Check for display controller status
     * @private
     */
    this._dc = function() {
        if (typeof dC == "undefined") {
            return SCS.err.showError(515, "_dc");
        } else {
            return SCS.err.showError(200, "_dc");
        }
    };
    
    /**
     * Shared functions for items
     * @private
     */
    this._item = function() {
        if (_container.length > 0) {
            $(".item .head .sender a:not(.profile)").live("click", function(e) {
                var $a = $(this);
                // Start private conversation with the contact
                e.preventDefault();
                dC.openConversationWithIdentity_($a.attr("href"));
            });
            $(".item .head .sender img, .item .head .sender span.profile").live("click", function(e) {
                var $i = $(this);
                // Open profile of the current user
                if ($i.attr("data-sender")) {
                    dC.showProfileOfIdentity_($i.attr("data-sender"));
                }
            });
            return SCS.err.showError(200, "_item");
        } else {
            return SCS.err.showError(510, "_item");
        }
    };
    
    /**
     * Add link actions to history items
     * @private
     */
    this._history = function() {
        if (_container.length > 0) {
            $(".item.history a").live("click", function(e) {
                var $a = $(this);
                var $nt = $(this).closest(".history");
                e.preventDefault();
                dC.loadHistoryInDays_($a.attr("href"));
            });
            return SCS.err.showError(200, "_history");
        } else {
            return SCS.err.showError(510, "_history");
        }
    };
    
    /**
     * Add play & stop voicemail functionality
     * @private
     */
    this._voicemail = function() {
        if (_container.length > 0) {
            $(".item.voicemail button").live("click", function() {
                var $b = $(this);
                var $vm = $(this).closest(".voicemail");
                
                // Play/stop voicemail
                if ($b.hasClass("control")) {
                    var $played = $(".progress", $vm);
                    if ($(this).hasClass("stop")) {
                        if (dC.stopVoicemailWithID_($vm.attr("id"))) {
                            self.voicemailStop($vm.attr("id"));
                        }
                    } else {
                        if (dC.playVoicemailWithID_($vm.attr("id"))) {
                            self.voicemailPlay($vm.attr("id"));
                        }
                    }
                }
                
                // Delete voicemail
                if ($b.hasClass("delete")) {
                    if (dC.stopVoicemailWithID_($vm.attr("id"))) {
                        if (dC.deleteVoicemailWithID_($vm.attr("id"))) {
                            $vm.animate({"opacity": 0, "height": 0}, 750, function() {
                                $vm.remove();
                            });
                        }
                    }
                }
            });
            return SCS.err.showError(200, "_voicemail");
        } else {
            return SCS.err.showError(510, "_voicemail");
        }
    };
    
    /**
     * Message functions
     * @private
     */
    this._message = function() {
        if (_container.length > 0) {
            $(".item.message button").live("click", function(e) {
                var $b = $(this);
                var $m = $(this).closest(".message");
                
                // Edit message
                if ($b.hasClass("edit")) {
                    dC.startMessageEditWithID_($m.attr("id"));
                }
                
                // Delete message
                if ($b.hasClass("delete")) {
                    dC.removeMessageWithID_($m.attr("id"));
                }                
            });
            $(".item.message.editable").live("mouseover", function() {
                var $m = $(this);
                
                // Ask from SkyLib if message is editable and remove editable status if False
                if (!dC.isMessageEditable_($m.attr("id"))) {
                    $m.removeClass("editable");
                }
                
            });
            $(".item.message.editable .body").live("dblclick", function(e) {
                var $m = $(this).closest(".item");
                if ($m.find(".editMode").length > 0) {
                    return;
                }
                
                if (dC.isMessageEditableInline_($m.attr("id"))) {
                    if (dC.startMessageInlineEditWithID_body_($m.attr("id"), $(".body", $m).html())) {
                        self.messageStartInlineEdit($m.attr("id"));
                    }
                }
            });
            
            // Cancel message editmode on “Escape”
            $(window).bind("keyup", function(e) {
                if (e.which == 27) {
                    $(".item .editMode").each(function() {
                        var $m = $(this).closest(".item");
                        if (dC.cancelMessageInlineEditWithID_($m.attr("id"))) {
                            self.messageEndInlineEdit($m.attr("id"));
                        }
                    });
                }
                
            });
            
            // Cancel inline editing if clicked somewhere else on the body
            $("body").bind("click", function(e) {
                if ($(e.target).hasClass("editMode")) {
                    return;
                }
                $(".item .editMode").each(function() {
                    var $m = $(this).closest(".item");
                    if (dC.cancelMessageInlineEditWithID_($m.attr("id"))) {
                        self.messageEndInlineEdit($m.attr("id"));
                    }
                });
            });
            
            // Save the message on “Enter” and allow line breaks with “Shift-Enter”
            $(".item.message.editable .editMode").live("keydown", function(e) {
                var $mb = $(this);
                var $m = $mb.closest(".item");
                if (e.which == 13) {
                    if (!e.shiftKey) {
                        e.preventDefault();
                        if (dC.stopMessageInlineEditAndSaveWithID_body_($m.attr("id"), $mb.html())) {
                            self.messageEndInlineEdit($m.attr("id"));
                        }
                    }
                }
            });
            
            return SCS.err.showError(200, "_message");
        } else {
            return SCS.err.showError(510, "_message");
        }
    };
    
    /**
     * Add file transfer item functionality
     * @private
     */
    this._transfer = function() {
        if (_container.length > 0) {
            
            // Open/close sub transfers in transfer item
            $(".item.transfer .main .name").live("click", function() {
                var $link = $(this);
                var $ft = $link.closest(".transfer");
                if ($(".sub", $ft).length > 0) {
                    if ($link.hasClass("open")) {
                        $link.removeClass("open");
                        $(".sub", $ft).removeClass("open");
                    } else {
                        $link.addClass("open");
                        $(".sub", $ft).addClass("open");
                    }
                }
            });
            
            // Icon in transfer
            $(".item.transfer .icon").live("dblclick", function() {
                var $icon = $(this);
                var $ft = $icon.closest(".transfer");
                var $ins = $icon.closest(".instance");
                dC.doubleClickIconWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins));
            });
            
            // Buttons in transfer item
            $(".item.transfer button").live("click", function() {
                var $b = $(this);
                var $ft = $b.closest(".transfer");
                var $ins = $b.closest(".instance");
                
                // Accept transfers
                if ($b.hasClass("accept")) {
                    if (dC.acceptTransferWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins))) {
                        self.transferAccept($ft.attr("id"), $(".instance", $ft).index($ins));
                    }
                }
                
                // Decline transfers
                if ($b.hasClass("decline")) {
                    if (dC.cancelTransferWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins))) {
                        self.transferCancel($ft.attr("id"), $(".instance", $ft).index($ins));
                    }
                }
                
                // Cancel running transfers
                if ($b.hasClass("cancel")) {
                    if (dC.cancelTransferWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins))) {
                        self.transferCancel($ft.attr("id"), $(".instance", $ft).index($ins));
                    }
                }
                
                // Delete transfers
                if ($b.hasClass("delete")) {
                    if (dC.deleteTransferWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins))) {
                        self.transferDelete($ft.attr("id"), $(".instance", $ft).index($ins));
                    }
                }
                
                // Quicklook file
                if ($b.hasClass("quicklook")) {
                    dC.showTransferInQuickLookWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins));
                }
                
                // Reveal transfer in Finder
                if ($b.hasClass("reveal")) {
                    dC.revealTransferInFinderWithID_index_($ft.attr("id"), $(".instance", $ft).index($ins));
                }
            });
            return SCS.err.showError(200, "_transfer");
        } else {
            return SCS.err.showError(510, "_transfer");
        }
    };
    
    /**
     * Check if current scrollposition is near the bottom of the screen
     * @private
     */
    this._nearBottom = function() {
        return (document.body.scrollTop+window.innerHeight >= document.body.offsetHeight-75);
    };
    
    /**
     * Append an item to end of the conversation and add an ID to the item
     * @param {String} html HTML markup of the item to be added
     * @param {Boolean} scroll If conversation should scroll to bottom after item is added
     * @return Response code with description
     * @type {String}
     * @see #prependItem
     * @see #appendBulk
     */    
    this.appendItem = function(html, scroll) {
        if (_container.length > 0) {
            var atEnd = self._nearBottom();
            if ($("#typing").length > 0) {
                $("#conversation #typing").before(html);
            } else {
                _container.append(html);
            }
            if (scroll && atEnd) {
                self.scrollToEnd();
            }
            return SCS.err.showError(200, "appendItem");
        } else {
            return SCS.err.showError(510, "appendItem");
        }
    };

    /**
     * Scroll the conversation to a specific item
     * @param {String} id ID of item to scroll to
     * @return Response code with description
     * @type {String}
     */    
    this.scrollToItem = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "scrollToItem");
        }
        if (_container.length > 0) {
            var $i = $("#"+id);
            if ($i.length > 0) {
                window.scrollTo(0,$i.get(0).offsetTop);
                return SCS.err.showError(200, "scrollToItem");
            } else {
                return SCS.err.showError(511, "scrollToItem");
            }
        } else {
            return SCS.err.showError(510, "scrollToItem");
        }
    };
    
    /**
     * Prepend an item to the beggining of the conversation and add an ID to the item
     * @param {String} html HTML markup of the item to be added
     * @param {Boolean} scroll If conversation should scroll to bottom after item is added
     * @return Response code with description
     * @type {String}
     * @see #appendItem
     * @see #appendBulk
     */    
    this.prependItem = function(html, scroll) {
        if (_container.length > 0) {
            _container.prepend(html);
            if (scroll && self._nearBottom()) {
                self.scrollToEnd();
            }
            return SCS.err.showError(200, "prependItem");
        } else {
            return SCS.err.showError(510, "prependItem");
        }
    };
    
    /**
     * Append a large chunk of messages at once
     * @param {String} html HTML markup to add as a bulk string
     * @param {Boolean} scroll If conversation should scroll to bottom after item is added
     * @param {String} origin ID of item used as the entry point
     * @param {String} location Add content before of after origin element. Possible values: before | after
     * @return Response code with description
     * @type {String}
     * @see #appendItem
     * @see #prependItem
     */    
    this.appendBulk = function(html, scroll, origin, location) {
        if (_container.length > 0) {
            var atEnd = self._nearBottom();
            if (typeof origin != "undefined" && origin != "" && $("#"+origin).length > 0 && typeof location != "undefined") {
                if (location == "before") {
                    $("#conversation #"+origin).before(html);
                } else if (location == "after") {
                    $("#conversation #"+origin).after(html);
                } else {
                    return SCS.err.showError(514, "appendBulk");
                }
            } else {
                if ($("#typing").length > 0) {
                    $("#conversation #typing").before(html);
                } else {
                    _container.append(html);
                }
            }
            if (scroll && atEnd) {
                self.scrollToEnd();
            }
            return SCS.err.showError(200, "appendBulk");
        } else {
            return SCS.err.showError(510, "appendBulk");
        }
    };

    /**
     * Remove an item from the conversation
     * @param {String} id ID of item to remove
     * @param {Boolean} scroll If conversation should scroll to bottom after item is removed
     * @return Response code with description
     * @type {String}
     * @see #appendItem
     * @see #prependItem
     * @see #appendBulk
     */    
    this.removeItem = function(id, scroll) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "removeItem");
        }
        if (_container.length > 0) {
            var $i = $("#"+id);
            if ($i.length > 0) {
                $("#"+id).remove();
                if (scroll || (self._nearBottom() && scroll != false)) {
                    self.scrollToEnd();
                }
                return SCS.err.showError(200, "removeItem");
            } else {
                return SCS.err.showError(511, "scrollToItem");
            }
        } else {
            return SCS.err.showError(510, "removeItem");
        }
    };

    /**
     * Update typing indicator
     * @param {String} status Possible values: show | hide
     * @param {String} html Markup to replace current content (if empty, indicator is hidden)
     * @param {Boolean} scroll If conversation should scroll to bottom after HTML is added
     * @return Response code with description
     * @type {String}
     */    
    this.typingUpdate = function(html, status, scroll) {
        if (_container.length > 0) {
            if (status == "hide" || html == "") {
                $("#typing").addClass("invisible");
            } else {
                if ($("#typing").length > 0) {
                    $("#typing").remove();
                }
                _container.append(html);
            }
            if (scroll || (self._nearBottom() && scroll != false)) {
                self.scrollToEnd();
            }
            return SCS.err.showError(200, "typingUpdate");
        } else {
            return SCS.err.showError(510, "typingUpdate");
        }
    };
    
    /**
     * Scroll the conversation all the way to the end
     * @return Response code with description
     * @type {String}
     */    
    this.scrollToEnd = function() {
        if (_container.length > 0) {
            window.scrollTo(0,_container.outerHeight());
            return SCS.err.showError(200, "scrollToEnd");
        } else {
            return SCS.err.showError(510, "scrollToEnd");
        }
    };
    
    /**
     * Accept a transfer
     * @deprecated
     * @param {String} id ID of item to select
     * @param {int} index Index of the transfer to select
     * @return Response code with description
     * @type {String}
     * @see #transferCancel
     */
    this.transferAccept = function(id, index) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "transferAccept");
        }
        if (_container.length > 0) {
            var $ft = $("#"+id);
            if ($ft.length > 0) {
                if (typeof index != "undefined" && typeof index == "number" && index >= 0) {
                    if (index == 0) {
                        $(".instance", $ft).addClass("running");
                        $(".progress", $ft).css("width", "auto");
                    } else {
                        $(".instance:eq("+index+")", $ft).addClass("running");
                        $(".progress:eq("+index+")", $ft).css("width", "auto");
                    }
                    return SCS.err.showError(200, "transferAccept");
                } else {
                    return SCS.err.showError(512, "transferAccept");
                }
            } else {
                return SCS.err.showError(511, "transferAccept");
            }
        } else {
            return SCS.err.showError(510, "transferAccept");
        }
    };
    
    /**
     * Cancel a transfer
     * @deprecated
     * @param {String} id ID of item to select
     * @param {int} index Index of the transfer to select
     * @return Response code with description
     * @type {String}
     * @see #transferAccept
     */
    this.transferCancel = function(id, index) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "transferCancel");
        }
        if (_container.length > 0) {
            var $ft = $("#"+id);
            if ($ft.length > 0) {
                if (typeof index != "undefined" && typeof index == "number" && index >= 0) {
                    if (index == 0) {
                        $(".instance", $ft).addClass("completed cancelled").removeClass("running waiting");
                    } else {
                        $(".instance:eq("+index+")", $ft).addClass("completed cancelled").removeClass("running waiting");
                    }
                    return SCS.err.showError(200, "transferCancel");
                } else {
                    return SCS.err.showError(512, "transferCancel");
                }
            } else {
                return SCS.err.showError(511, "transferCancel");
            }
        } else {
            return SCS.err.showError(510, "transferCancel");
        }
    };
    
    /**
     * Remove waiting status from a transfer
     * @deprecated
     * @param {String} id ID of item to select
     * @param {int} index Index of the transfer to select
     * @return Response code with description
     * @type {String}
     * @see #transferCancel
     */
    this.transferReady = function(id, index) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "transferReady");
        }
        if (_container.length > 0) {
            var $ft = $("#"+id);
            if ($ft.length > 0) {
                if (typeof index != "undefined" && typeof index == "number" && index >= 0) {
                    if (index == 0) {
                        $(".instance", $ft).removeClass("waiting");
                    } else {
                        $(".instance:eq("+index+")", $ft).removeClass("waiting");
                    }
                    return SCS.err.showError(200, "transferReady");
                } else {
                    return SCS.err.showError(512, "transferReady");
                }
            } else {
                return SCS.err.showError(511, "transferReady");
            }
        } else {
            return SCS.err.showError(510, "transferReady");
        }
    };

        
    /**
     * Set transfer position for file transfer
     * @param {String} id ID of item to select
     * @param {int} index Index of the transfer to select
     * @param {int} percentage Progressbar fill percentage
     * @param {String} text Set as status text
     * @param {Boolean} connecting (Optional) Set to true if transfer has gone to connecting status
     * @return Response code with description
     * @type {String}
     */
    this.transferPosition = function(id, index, percentage, text, connecting) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "transferPosition");
        }
        if (typeof index != "number" || index < 0) {
            return SCS.err.showError(512, "transferPosition");
        }
        if (typeof text == "undefined") {
            text = "";
        }
        if (percentage > 100) {
            percentage = 100;
        } else if (percentage < 0) {
            percentage = 0;
        }
        $ft = $("#"+id+"");
        if (_container.length > 0) {
            if ($ft.length > 0 && $(".progress:eq("+index+")", $ft).length > 0 && $(".size:eq("+index+")", $ft).length > 0) {
                if (typeof connecting == "undefined" || connecting == false) {
                    $(".progress:eq("+index+")", $ft).removeClass("hidden").css("width", percentage+"%");
                } else {
                    $(".progress:eq("+index+")", $ft).addClass("hidden").css("width", "");
                }
                $(".size:eq("+index+")", $ft).html(text);
                return SCS.err.showError(200, "transferPosition");
            } else {
                return SCS.err.showError(511, "transferPosition");
            }
        } else {
            return SCS.err.showError(510, "transferPosition");
        }
    };
    
    /**
     * Complete a transfer
     * @deprecated
     * @param {String} id ID of item to select
     * @param {int} index Index of the transfer to select
     * @return Response code with description
     * @type {String}
     */
    this.transferComplete = function(id, index) {
        if (_container.length > 0) {
            var $ft = $("#"+id);
            if ($ft.length > 0) {
                if (typeof index != "undefined" && typeof index == "number" && index >= 0) {
                    if (index == 0) {
                        $(".instance", $ft).addClass("completed").removeClass("running");
                    } else {
                        $(".instance:eq("+index+")", $ft).addClass("completed").removeClass("running");
                    }
                    return SCS.err.showError(200, "transferComplete");
                } else {
                    return SCS.err.showError(512, "transferComplete");
                }
            } else {
                return SCS.err.showError(511, "transferComplete");
            }
        } else {
            return SCS.err.showError(510, "transferComplete");
        }
    };
    
    /**
     * Update transfer status in the head
     * @param {String} id ID of item to select
     * @param {int} index Index of the time element to select
     * @param {String} status Set the classname for the status, possible values: cancelled | sending | rejected | sent | missing
     * @param {String} text Set as status text
     * @return Response code with description
     * @type {String}
     */
    this.transferUpdateHead = function(id, index, status, text) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "transferUpdateHead");
        }
        var $ft = $("#"+id+"");
        if (typeof text == "undefined") {
            text = "";
        }
        if (typeof index != "number" || typeof index == "undefined") {
            return SCS.err.showError(512, "transferUpdateHead");
        }
        if (_container.length > 0) {
            if ($ft.length > 0 && $(".time:eq("+index+")", $ft).length > 0) {
                $(".time:eq("+index+")", $ft).removeClass("cancelled rejected sending sent missing").addClass(status).html(text);
                return SCS.err.showError(200, "transferUpdateHead");
            } else {
                return SCS.err.showError(511, "transferUpdateHead");
            }
        } else {
            return SCS.err.showError(510, "transferUpdateHead");
        }
    };
    
    /**
     * Update transfer objects in a transfer item
     * @param {String} id ID of item to select
     * @param {int} index Index of the transfer
     * @param {String} status Set the classname for the status, possible values: waiting | running | connecting | completed | cancelled | failed | missing
     * @return Response code with description
     * @type {String}
     */
    this.transferUpdateStatus = function(id, index, status) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "transferUpdateStatus");
        }
        var $ft = $("#"+id+"");
        if (typeof text == "undefined") {
            text = "";
        }
        if (typeof index != "number" || typeof index == "undefined") {
            return SCS.err.showError(512, "transferUpdateStatus");
        }
        if (_container.length > 0) {
            if ($ft.length > 0 && $(".instance:eq("+index+")", $ft).length > 0) {
                $(".instance:eq("+index+")", $ft).removeClass("waiting running connecting completed cancelled failed missing").addClass(status);
                return SCS.err.showError(200, "transferUpdateStatus");
            } else {
                return SCS.err.showError(511, "transferUpdateStatus");
            }
        } else {
            return SCS.err.showError(510, "transferUpdateStatus");
        }
    };
    
    /**
     * Play a voicemail
     * @param {String} id ID of item to select
     * @return Response code with description
     * @type {String}
     * @see #voicemailStop
     */
    this.voicemailPlay = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "voicemailPlay");
        }
        var $vm = $("#"+id+"");
        if (_container.length > 0) {
            if ($vm.length > 0) {
                $vm.removeClass("new");
                $(".control", $vm).removeClass("play").addClass("stop");
                $(".progress", $vm).removeClass("hidden").css("width", "");
                return SCS.err.showError(200, "voicemailPlay");
            } else {
                return SCS.err.showError(511, "voicemailPlay");
            }
        } else {
            return SCS.err.showError(510, "voicemailPlay");
        }
    };
    
    /**
     * Stop a voicemail
     * @param {String} id ID of item to select
     * @return Response code with description
     * @type {String}
     * @see #voicemailPlay
     */
    this.voicemailStop = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "voicemailStop");
        }
        var $vm = $("#"+id+"");
        if (_container.length > 0) {
            if ($vm.length > 0) {
                $(".control", $vm).removeClass("stop").addClass("play");
                $(".progress", $vm).addClass("hidden").css("width","");
                return SCS.err.showError(200, "voicemailStop");
            } else {
                return SCS.err.showError(511, "voicemailStop");
            }
        } else {
            return SCS.err.showError(510, "voicemailStop");
        }
    };
    
    /**
     * Set voicemail play progress
     * @param {String} id ID of item to select
     * @param {String} percentage Playhead location percentage
     * @param {String} time Voicemail played time
     * @return Response code with description
     * @type {String}
     */
    this.voicemailPosition = function(id, percentage, time) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "voicemailPosition");
        }
        if (typeof time == "undefined") {
            time = "";
        }
        if (percentage > 100) {
            percentage = 100;
        } else if (percentage < 0) {
            percentage = 0;
        }
        var $vm = $("#"+id+"");
        if (_container.length > 0) {
            if ($vm.length > 0 && $(".progress", $vm).length > 0 && $(".played", $vm).length > 0) {
                $(".progress", $vm).removeClass("hidden").css("width", percentage+"%");
                $(".played", $vm).html(time);
                return SCS.err.showError(200, "voicemailPosition");
            } else {
                return SCS.err.showError(511, "voicemailPosition");
            }
        } else {
            return SCS.err.showError(510, "voicemailPosition");
        }
    };
    
    /**
     * Update SMS status
     * @param {String} id ID of item to select
     * @param {int} index Index of the SMS head to select
     * @param {String} status Set the classname for the status, possible values: sending | failed | sent
     * @param {String} text Set as status text
     * @return Response code with description
     * @type {String}
     */
    this.smsUpdateStatus = function(id, index, status, text) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "smsUpdateStatus");
        }
        var $s = $("#"+id+"");
        if (typeof text == "undefined") {
            text = "";
        }
        if (typeof index != "number" || typeof index == "undefined") {
            return SCS.err.showError(512, "smsUpdateStatus");
        }
        if (_container.length > 0) {
            if ($s.length > 0 && $(".time:eq("+index+")", $s).length > 0) {
                $(".time:eq("+index+")", $s).removeClass("failed sending sent").addClass(status).html(text);
                return SCS.err.showError(200, "smsUpdateStatus");
            } else {
                return SCS.err.showError(511, "smsUpdateStatus");
            }
        } else {
            return SCS.err.showError(510, "smsUpdateStatus");
        }
    };
    
    /**
     * Mark message as read (works for both SMS and chat messages)
     * @param {String} id ID of item to select
     * @param {String} status Possible values read | unread | edited | deleted | sending
     * @return Response code with description
     * @type {String}
     */
    this.messageMarkAs = function(id, status) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageMarkAs");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0) {
                if (status == "read") {
                    $m.addClass(status);
                } else if (status == "unread") {
                    $m.removeClass("read");
                } else {
                    $m.removeClass("edited deleted sending").addClass(status);
                }
                return SCS.err.showError(200, "messageMarkAs");            
            } else {
                return SCS.err.showError(511, "messageMarkAs");
            }
        } else {
            return SCS.err.showError(510, "messageMarkAs");
        }
    };

    /**
     * Enable editing a message
     * @param {String} id ID of item to select
     * @return Response code with description
     * @type {String}
     * @see #messageDisableEdit
     */
    this.messageEnableEdit = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageEnableEdit");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0) {
                $m.addClass("editable");
                return SCS.err.showError(200, "messageEnableEdit");
            } else {
                return SCS.err.showError(511, "messageEnableEdit");
            }
        } else {
            return SCS.err.showError(510, "messageEnableEdit");
        }
    };
    
    /**
     * Disable editing a message
     * @param {String} id ID of item to select
     * @return Response code with description
     * @type {String}
     * @see #messageEnableEdit
     */
    this.messageDisableEdit = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageDisableEdit");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0) {
                $m.removeClass("editable");
                return SCS.err.showError(200, "messageDisableEdit");
            } else {
                return SCS.err.showError(511, "messageDisableEdit");
            }
        } else {
            return SCS.err.showError(510, "messageDisableEdit");
        }
    };
    
    /**
     * Update message contents, used after editing or deleting (=set message content as empty) a message
     * @param {String} id ID of item to select
     * @param {String} message Message contents, use null if no need to update
     * @param {String} time Time slot contents, use null if no need to update
     * @status {String} status (Optional) Set the message status. Possible values: deleted | edited
     * @return Response code with description
     * @type {String}
     */
    this.messageUpdate = function(id, message, time, status) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageUpdate");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0 && $m.hasClass("message")) {
                if (status != "undefined" && status != null) {
                    self.messageMarkAs(id, status);
                }
                if (message != null) {
                    $(".body", $m).html(message);
                }
                if (time != null) {
                    $(".time", $m).html(time);
                }
                return SCS.err.showError(200, "messageUpdate");
            } else {
                return SCS.err.showError(511, "messageUpdate");
            }
        } else {
            return SCS.err.showError(510, "messageUpdate");
        }
    };
    
    /**
     * Toggle message followup status
     * @param {String} id ID of item to select
     * @param {Boolean} followup If true, sets message to be a followup message
     * @return Response code with description
     * @type {String}
     */
    this.messageFollowupStatus = function(id, followup) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageFollowupStatus");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0 && $m.hasClass("message")) {
                if (followup == false) {
                    $m.removeClass("followup");
                } else {
                    $m.addClass("followup");
                }
                return SCS.err.showError(200, "messageFollowupStatus");
            } else {
                return SCS.err.showError(511, "messageFollowupStatus");
            }
        } else {
            return SCS.err.showError(510, "messageFollowupStatus");
        }
    };
    
    /**
     * Start inline editing of a messsage
     * @param {String} id ID of item to select
     * @type {String}
     */
    this.messageStartInlineEdit = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageStartInlineEdit");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0 && $m.hasClass("message")) {
                $m.find(".body").attr("contentEditable", "true").addClass("editMode");
                return SCS.err.showError(200, "messageStartInlineEdit");
            } else {
                return SCS.err.showError(511, "messageStartInlineEdit");
            }
        } else {
            return SCS.err.showError(510, "messageStartInlineEdit");
        }
    };
    
    /**
     * End inline editing of a messsage
     * @param {String} id ID of item to select
     * @type {String}
     */
    this.messageEndInlineEdit = function(id) {
        if (typeof id == "undefined" || id == "") {
            return SCS.err.showError(513, "messageEndInlineEdit");
        }
        var $m = $("#"+id+"");
        if (_container.length > 0) {
            if ($m.length > 0 && $m.hasClass("message")) {
                $m.find(".body").attr("contentEditable", "false").removeClass("editMode");
                return SCS.err.showError(200, "messageEndInlineEdit");
            } else {
                return SCS.err.showError(511, "messageEndInlineEdit");
            }
        } else {
            return SCS.err.showError(510, "messageEndInlineEdit");
        }
    };
    
    /**
     * Init SCS.Conversation object
     * @return Response code with description
     * @type {String}
     */
    this.init = function() {
        _container = $("#conversation");
        self._dc();
        self._init();
        self._item();
        self._history();
        self._message();
        self._voicemail();
        self._transfer();
        return SCS.err.showError(200, "init");
    };
    
};

/**
 * Create new identification object on startup
 * @private
 */
SCS.id = new SCS.Identifier();

/**
 * Create new conversation object on page start
 * @private
 */
SCS.conv = new SCS.Conversation();

/**
 * Create new conversation object on page start
 * @private
 */
SCS.err = new SCS.ErrorHandler();

$(document).ready(function() {
    SCS.conv.init();
});
