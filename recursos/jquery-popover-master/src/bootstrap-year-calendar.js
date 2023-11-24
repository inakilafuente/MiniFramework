/* 
 * Bootstrap year calendar v1.1.0
 * Created by Paul David-Sivelle
 * Licensed under the Apache License, Version 2.0
 */

!function (e) {
    var t = function (e, t) {
        this.element = e, this.element.addClass("calendar"), this._initializeEvents(t), this._initializeOptions(t), this._render()
    };
    t.prototype = {
        constructor: t, _initializeOptions: function (t) {
            null == t && (t = []), this.options = {
                startYear: isNaN(parseInt(t.startYear)) ? (new Date).getFullYear() : parseInt(t.startYear),
                minDate: t.minDate instanceof Date ? t.minDate : null,
                maxDate: t.maxDate instanceof Date ? t.maxDate : null,
                language: null != t.language && null != n[t.language] ? t.language : "en",
                allowOverlap: null != t.allowOverlap ? t.allowOverlap : !0,
                displayWeekNumber: null != t.displayWeekNumber ? t.displayWeekNumber : !1,
                alwaysHalfDay: null != t.alwaysHalfDay ? t.alwaysHalfDay : !1,
                enableRangeSelection: null != t.enableRangeSelection ? t.enableRangeSelection : !1,
                disabledDays: t.disabledDays instanceof Array ? t.disabledDays : [],
                roundRangeLimits: null != t.roundRangeLimits ? t.roundRangeLimits : !1,
                dataSource: t.dataSource instanceof Array != null ? t.dataSource : [],
                style: "background" == t.style || "border" == t.style || "custom" == t.style ? t.style : "border",
                enableContextMenu: null != t.enableContextMenu ? t.enableContextMenu : !1,
                contextMenuItems: t.contextMenuItems instanceof Array ? t.contextMenuItems : [],
                customDayRenderer: e.isFunction(t.customDayRenderer) ? t.customDayRenderer : null,
                customDataSourceRenderer: e.isFunction(t.customDataSourceRenderer) ? t.customDataSourceRenderer : null
            }, this._initializeDatasourceColors()
        }, _initializeEvents: function (e) {
            null == e && (e = []), e.renderEnd && this.element.bind("renderEnd", e.renderEnd), e.clickDay && this.element.bind("clickDay", e.clickDay), e.dayContextMenu && this.element.bind("dayContextMenu", e.dayContextMenu), e.selectRange && this.element.bind("selectRange", e.selectRange), e.mouseOnDay && this.element.bind("mouseOnDay", e.mouseOnDay), e.mouseOutDay && this.element.bind("mouseOutDay", e.mouseOutDay)
        }, _initializeDatasourceColors: function () {
            for (var e in this.options.dataSource)null == this.options.dataSource[e].color && (this.options.dataSource[e].color = a[e % a.length])
        }, _render: function () {
            this.element.empty(), this._renderHeader(), this._renderBody(), this._renderDataSource(), this._applyEvents(), this.element.find(".months-container").fadeIn(500), this._triggerEvent("renderEnd", {currentYear: this.options.startYear})
        }, _renderHeader: function () {
            var t = e(document.createElement("div"));
            t.addClass("calendar-header panel panel-default");
            var n = e(document.createElement("table")), a = e(document.createElement("th"));
            a.addClass("prev"), null != this.options.minDate && this.options.minDate > new Date(this.options.startYear - 1, 11, 31) && a.addClass("disabled");
            var s = e(document.createElement("span"));
            s.addClass("glyphicon glyphicon-chevron-left"), a.append(s), n.append(a);
            var i = e(document.createElement("th"));
            i.addClass("year-title year-neighbor2 hidden-sm hidden-xs"), i.text(this.options.startYear - 2), null != this.options.minDate && this.options.minDate > new Date(this.options.startYear - 2, 11, 31) && i.addClass("disabled"), n.append(i);
            var o = e(document.createElement("th"));
            o.addClass("year-title year-neighbor hidden-xs"), o.text(this.options.startYear - 1), null != this.options.minDate && this.options.minDate > new Date(this.options.startYear - 1, 11, 31) && o.addClass("disabled"), n.append(o);
            var r = e(document.createElement("th"));
            r.addClass("year-title"), r.text(this.options.startYear), n.append(r);
            var d = e(document.createElement("th"));
            d.addClass("year-title year-neighbor hidden-xs"), d.text(this.options.startYear + 1), null != this.options.maxDate && this.options.maxDate < new Date(this.options.startYear + 1, 0, 1) && d.addClass("disabled"), n.append(d);
            var l = e(document.createElement("th"));
            l.addClass("year-title year-neighbor2 hidden-sm hidden-xs"), l.text(this.options.startYear + 2), null != this.options.maxDate && this.options.maxDate < new Date(this.options.startYear + 2, 0, 1) && l.addClass("disabled"), n.append(l);
            var u = e(document.createElement("th"));
            u.addClass("next"), null != this.options.maxDate && this.options.maxDate < new Date(this.options.startYear + 1, 0, 1) && u.addClass("disabled");
            var c = e(document.createElement("span"));
            c.addClass("glyphicon glyphicon-chevron-right"), u.append(c), n.append(u), t.append(n), this.element.append(t)
        }, _renderBody: function () {
            var t = e(document.createElement("div"));
            t.addClass("months-container");
            for (var a = 0; 12 > a; a++) {
                var s = e(document.createElement("div"));
                s.addClass("month-container"), s.data("month-id", a);
                var i = new Date(this.options.startYear, a, 1), o = e(document.createElement("table"));
                o.addClass("month");
                var r = e(document.createElement("thead")), d = e(document.createElement("tr")), l = e(document.createElement("th"));
                l.addClass("month-title"), l.attr("colspan", this.options.displayWeekNumber ? 8 : 7), l.text(n[this.options.language].months[a]), d.append(l), r.append(d);
                var u = e(document.createElement("tr"));
                if (this.options.displayWeekNumber) {
                    var c = e(document.createElement("th"));
                    c.addClass("week-number"), c.text(n[this.options.language].weekShort), u.append(c)
                }
                var h = n[this.options.language].weekStart;
                do {
                    var m = e(document.createElement("th"));
                    m.addClass("day-header"), m.text(n[this.options.language].daysMin[h]), u.append(m), h++, h >= 7 && (h = 0)
                } while (h != n[this.options.language].weekStart);
                r.append(u), o.append(r);
                for (var p = new Date(i.getTime()), g = new Date(this.options.startYear, a + 1, 0), f = n[this.options.language].weekStart; p.getDay() != f;)p.setDate(p.getDate() - 1);
                for (; g >= p;) {
                    var D = e(document.createElement("tr"));
                    if (this.options.displayWeekNumber) {
                        var c = e(document.createElement("td"));
                        c.addClass("week-number"), c.text(this.getWeekNumber(p)), D.append(c)
                    }
                    do {
                        var y = e(document.createElement("td"));
                        if (y.addClass("day"), i > p)y.addClass("old"); else if (p > g)y.addClass("new"); else {
                            if (null != this.options.minDate && p < this.options.minDate || null != this.options.maxDate && p > this.options.maxDate)y.addClass("disabled"); else if (this.options.disabledDays.length > 0)for (var h in this.options.disabledDays)if (p.getTime() == this.options.disabledDays[h].getTime()) {
                                y.addClass("disabled");
                                break
                            }
                            var v = e(document.createElement("div"));
                            v.addClass("day-content"), v.text(p.getDate()), y.append(v), this.options.customDayRenderer && this.options.customDayRenderer(v, p)
                        }
                        D.append(y), p.setDate(p.getDate() + 1)
                    } while (p.getDay() != f);
                    o.append(D)
                }
                s.append(o), t.append(s)
            }
            this.element.append(t)
        }, _renderDataSource: function () {
            var t = this;
            null != this.options.dataSource && this.options.dataSource.length > 0 && this.element.find(".month-container").each(function () {
                var n = e(this).data("month-id"), a = new Date(t.options.startYear, n, 1), s = new Date(t.options.startYear, n + 1, 0);
                if ((null == t.options.minDate || s >= t.options.minDate) && (null == t.options.maxDate || a <= t.options.maxDate)) {
                    var i = [];
                    for (var o in t.options.dataSource)(!(t.options.dataSource[o].startDate > s) || t.options.dataSource[o].endDate < a) && i.push(t.options.dataSource[o]);
                    i.length > 0 && e(this).find(".day-content").each(function () {
                        var a = new Date(t.options.startYear, n, e(this).text()), s = [];
                        if ((null == t.options.minDate || a >= t.options.minDate) && (null == t.options.maxDate || a <= t.options.maxDate)) {
                            for (var o in i)i[o].startDate <= a && i[o].endDate >= a && s.push(i[o]);
                            s.length > 0 && t._renderDataSourceDay(e(this), a, s)
                        }
                    })
                }
            })
        }, _renderDataSourceDay: function (e, t, n) {
            switch (this.options.style) {
                case"border":
                    var a = 0;
                    if (1 == n.length ? a = 4 : n.length <= 3 ? a = 2 : e.parent().css("box-shadow", "inset 0 -4px 0 0 black"), a > 0) {
                        var s = "";
                        for (var i in n)"" != s && (s += ","), s += "inset 0 -" + (parseInt(i) + 1) * a + "px 0 0 " + n[i].color;
                        e.parent().css("box-shadow", s)
                    }
                    break;
                case"background":
                    e.parent().css("background-color", n[n.length - 1].color);
                    var o = t.getTime();
                    if (n[n.length - 1].startDate.getTime() == o)if (e.parent().addClass("day-start"), n[n.length - 1].startHalfDay || this.options.alwaysHalfDay) {
                        e.parent().addClass("day-half");
                        for (var r = "transparent", i = n.length - 2; i >= 0; i--)if (n[i].startDate.getTime() != o || !n[i].startHalfDay && !this.options.alwaysHalfDay) {
                            r = n[i].color;
                            break
                        }
                        e.parent().css("background", "linear-gradient(-45deg, " + n[n.length - 1].color + ", " + n[n.length - 1].color + " 49%, " + r + " 51%, " + r + ")")
                    } else this.options.roundRangeLimits && e.parent().addClass("round-left"); else if (n[n.length - 1].endDate.getTime() == o)if (e.parent().addClass("day-end"), n[n.length - 1].endHalfDay || this.options.alwaysHalfDay) {
                        e.parent().addClass("day-half");
                        for (var r = "transparent", i = n.length - 2; i >= 0; i--)if (n[i].endDate.getTime() != o || !n[i].endHalfDay && !this.options.alwaysHalfDay) {
                            r = n[i].color;
                            break
                        }
                        e.parent().css("background", "linear-gradient(135deg, " + n[n.length - 1].color + ", " + n[n.length - 1].color + " 49%, " + r + " 51%, " + r + ")")
                    } else this.options.roundRangeLimits && e.parent().addClass("round-right");
                    break;
                case"custom":
                    this.options.customDataSourceRenderer && this.options.customDataSourceRenderer.call(this, e, t, n)
            }
        }, _applyEvents: function () {
            var t = this;
            this.element.find(".year-neighbor, .year-neighbor2").click(function () {
                e(this).hasClass("disabled") || t.setYear(parseInt(e(this).text()))
            }), this.element.find(".calendar-header .prev").click(function () {
                e(this).hasClass("disabled") || t.element.find(".months-container").animate({"margin-left": "100%"}, 100, function () {
                    t.element.find(".months-container").hide(), t.element.find(".months-container").css("margin-left", "0"), setTimeout(function () {
                        t.setYear(t.options.startYear - 1)
                    }, 50)
                })
            }), this.element.find(".calendar-header .next").click(function () {
                e(this).hasClass("disabled") || t.element.find(".months-container").animate({"margin-left": "-100%"}, 100, function () {
                    t.element.find(".months-container").hide(), t.element.find(".months-container").css("margin-left", "0"), setTimeout(function () {
                        t.setYear(t.options.startYear + 1)
                    }, 50)
                })
            });
            var n = this.element.find(".day:not(.old, .new, .disabled)");
            n.click(function (n) {
                n.stopPropagation();
                var a = t._getDate(e(this));
                t._triggerEvent("clickDay", {element: e(this), which: n.which, date: a, events: t.getEvents(a)})
            }), n.bind("contextmenu", function (n) {
                t.options.enableContextMenu && (n.preventDefault(), t.options.contextMenuItems.length > 0 && t._openContextMenu(e(this)));
                var a = t._getDate(e(this));
                t._triggerEvent("dayContextMenu", {element: e(this), date: a, events: t.getEvents(a)})
            }), this.options.enableRangeSelection && (n.mousedown(function (n) {
                if (1 == n.which) {
                    var a = t._getDate(e(this));
                    (t.options.allowOverlap || 0 == t.getEvents(a).length) && (t._mouseDown = !0, t._rangeStart = t._rangeEnd = a, t._refreshRange())
                }
            }), n.mouseenter(function (n) {
                if (t._mouseDown) {
                    var a = t._getDate(e(this));
                    if (!t.options.allowOverlap) {
                        var s = new Date(t._rangeStart.getTime());
                        if (a > s)for (var i = new Date(s.getFullYear(), s.getMonth(), s.getDate() + 1); a > s && !(t.getEvents(i).length > 0);)s.setDate(s.getDate() + 1), i.setDate(i.getDate() + 1); else for (var i = new Date(s.getFullYear(), s.getMonth(), s.getDate() - 1); s > a && !(t.getEvents(i).length > 0);)s.setDate(s.getDate() - 1), i.setDate(i.getDate() - 1);
                        a = s
                    }
                    var o = t._rangeEnd;
                    t._rangeEnd = a, o.getTime() != t._rangeEnd.getTime() && t._refreshRange()
                }
            }), e(window).mouseup(function (e) {
                if (t._mouseDown) {
                    t._mouseDown = !1, t._refreshRange();
                    var n = t._rangeStart < t._rangeEnd ? t._rangeStart : t._rangeEnd, a = t._rangeEnd > t._rangeStart ? t._rangeEnd : t._rangeStart;
                    t._triggerEvent("selectRange", {startDate: n, endDate: a})
                }
            })), n.mouseenter(function (n) {
                if (!t._mouseDown) {
                    var a = t._getDate(e(this));
                    t._triggerEvent("mouseOnDay", {element: e(this), date: a, events: t.getEvents(a)})
                }
            }), n.mouseleave(function (n) {
                var a = t._getDate(e(this));
                t._triggerEvent("mouseOutDay", {element: e(this), date: a, events: t.getEvents(a)})
            }), setInterval(function () {
                var n = e(t.element).width(), a = e(t.element).find(".month").first().width() + 10, s = "month-container";
                s += n > 6 * a ? " col-xs-2" : n > 4 * a ? " col-xs-3" : n > 3 * a ? " col-xs-4" : n > 2 * a ? " col-xs-6" : " col-xs-12", e(t.element).find(".month-container").attr("class", s)
            }, 300)
        }, _refreshRange: function () {
            var t = this;
            if (this.element.find("td.day.range").removeClass("range"), this.element.find("td.day.range-start").removeClass("range-start"), this.element.find("td.day.range-end").removeClass("range-end"), this._mouseDown) {
                var n = t._rangeStart < t._rangeEnd ? t._rangeStart : t._rangeEnd, a = t._rangeEnd > t._rangeStart ? t._rangeEnd : t._rangeStart;
                this.element.find(".month-container").each(function () {
                    var s = e(this).data("month-id");
                    n.getMonth() <= s && a.getMonth() >= s && e(this).find("td.day:not(.old, .new)").each(function () {
                        var s = t._getDate(e(this));
                        s >= n && a >= s && (e(this).addClass("range"), s.getTime() == n.getTime() && e(this).addClass("range-start"), s.getTime() == a.getTime() && e(this).addClass("range-end"))
                    })
                })
            }
        }, _openContextMenu: function (t) {
            var n = e(".calendar-context-menu");
            n.length > 0 ? (n.hide(), n.empty()) : (n = e(document.createElement("div")), n.addClass("calendar-context-menu"), e("body").append(n));
            var a = this._getDate(t), s = this.getEvents(a);
            for (var i in s) {
                var o = e(document.createElement("div"));
                o.addClass("item"), o.css("border-left", "4px solid " + s[i].color);
                var r = e(document.createElement("div"));
                r.addClass("content"), r.text(s[i].name), o.append(r);
                var d = e(document.createElement("span"));
                d.addClass("glyphicon glyphicon-chevron-right"), o.append(d), this._renderContextMenuItems(o, this.options.contextMenuItems, s[i]), n.append(o)
            }
            n.children().length > 0 && (n.css("left", t.offset().left + 25 + "px"), n.css("top", t.offset().top + 25 + "px"), n.show(), e(window).one("mouseup", function () {
                n.hide()
            }))
        }, _renderContextMenuItems: function (t, n, a) {
            var s = e(document.createElement("div"));
            s.addClass("submenu");
            for (var i in n)if (!n[i].visible || n[i].visible(a)) {
                var o = e(document.createElement("div"));
                o.addClass("item");
                var r = e(document.createElement("div"));
                r.addClass("content"), r.text(n[i].text), o.append(r), n[i].click && !function (e) {
                    o.click(function () {
                        n[e].click(a)
                    })
                }(i);
                var d = e(document.createElement("span"));
                d.addClass("glyphicon glyphicon-chevron-right"), o.append(d), n[i].items && n[i].items.length > 0 && this._renderContextMenuItems(o, n[i].items, a), s.append(o)
            }
            s.children().length > 0 && t.append(s)
        }, _getColor: function (t) {
            var n = e("<div />");
            n.css("color", t)
        }, _getDate: function (e) {
            var t = e.children(".day-content").text(), n = e.closest(".month-container").data("month-id"), a = this.options.startYear;
            return new Date(a, n, t)
        }, _triggerEvent: function (t, n) {
            var a = e.Event(t);
            for (var s in n)a[s] = n[s];
            this.element.trigger(a)
        }, getWeekNumber: function (e) {
            var t = new Date(e.getTime());
            t.setHours(0, 0, 0, 0), t.setDate(t.getDate() + 3 - (t.getDay() + 6) % 7);
            var n = new Date(t.getFullYear(), 0, 4);
            return 1 + Math.round(((t.getTime() - n.getTime()) / 864e5 - 3 + (n.getDay() + 6) % 7) / 7)
        }, getEvents: function (e) {
            var t = [];
            if (this.options.dataSource && e)for (var n in this.options.dataSource)this.options.dataSource[n].startDate <= e && this.options.dataSource[n].endDate >= e && t.push(this.options.dataSource[n]);
            return t
        }, getYear: function () {
            return this.options.startYear
        }, setYear: function (e) {
            var t = parseInt(e);
            isNaN(t) || (this.options.startYear = t, this._render())
        }, getMinDate: function () {
            return this.options.minDate
        }, setMinDate: function (e) {
            e instanceof Date && (this.options.minDate = e, this._render())
        }, getMaxDate: function () {
            return this.options.maxDate
        }, setMaxDate: function (e) {
            e instanceof Date && (this.options.maxDate = e, this._render())
        }, getStyle: function () {
            return this.options.style
        }, setStyle: function (e) {
            this.options.style = "background" == e || "border" == e || "custom" == e ? e : "border", this._render()
        }, getAllowOverlap: function () {
            return this.options.allowOverlap
        }, setAllowOverlap: function (e) {
            this.options.allowOverlap = e
        }, getDisplayWeekNumber: function () {
            return this.options.displayWeekNumber
        }, setDisplayWeekNumber: function (e) {
            this.options.displayWeekNumber = e, this._render()
        }, getAlwaysHalfDay: function () {
            return this.options.alwaysHalfDay
        }, setAlwaysHalfDay: function (e) {
            this.options.alwaysHalfDay = e, this._render()
        }, getEnableRangeSelection: function () {
            return this.options.enableRangeSelection
        }, setEnableRangeSelection: function (e) {
            this.options.enableRangeSelection = e, this._render()
        }, getDisabledDays: function () {
            return this.options.disabledDays
        }, setDisabledDays: function (e) {
            this.options.disabledDays = e instanceof Array ? e : [], this._render()
        }, getRoundRangeLimits: function () {
            return this.options.roundRangeLimits
        }, setRoundRangeLimits: function (e) {
            this.options.roundRangeLimits = e, this._render()
        }, getEnableContextMenu: function () {
            return this.options.enableContextMenu
        }, setEnableContextMenu: function (e) {
            this.options.enableContextMenu = e, this._render()
        }, getContextMenuItems: function () {
            return this.options.contextMenuItems
        }, setContextMenuItems: function (e) {
            this.options.contextMenuItems = e instanceof Array ? e : [], this._render()
        }, getCustomDayRenderer: function () {
            return this.options.customDayRenderer
        }, setCustomDayRenderer: function (t) {
            this.options.customDayRenderer = e.isFunction(t) ? t : null, this._render()
        }, getCustomDataSourceRenderer: function () {
            return this.options.customDataSourceRenderer
        }, setCustomDataSourceRenderer: function (t) {
            this.options.customDataSourceRenderer = e.isFunction(t) ? t : null, this._render()
        }, getLanguage: function () {
            return this.options.language
        }, setLanguage: function (e) {
            null != e && null != n[e] && (this.options.language = e, this._render())
        }, getDataSource: function () {
            return this.options.dataSource
        }, setDataSource: function (e) {
            this.options.dataSource = e instanceof Array ? e : [], this._initializeDatasourceColors(), this._render()
        }, addEvent: function (e) {
            this.options.dataSource.push(e), this._render()
        }
    }, e.fn.calendar = function (n) {
        var a = new t(e(this), n);
        return e(this).data("calendar", a), a
    }, e.fn.renderEnd = function (t) {
        e(this).bind("renderEnd", t)
    }, e.fn.clickDay = function (t) {
        e(this).bind("clickDay", t)
    }, e.fn.dayContextMenu = function (t) {
        e(this).bind("dayContextMenu", t)
    }, e.fn.selectRange = function (t) {
        e(this).bind("selectRange", t)
    }, e.fn.mouseOnDay = function (t) {
        e(this).bind("mouseOnDay", t)
    }, e.fn.mouseOutDay = function (t) {
        e(this).bind("mouseOutDay", t)
    };
    var n = e.fn.calendar.dates = {
        en: {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
            months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            weekShort: "W",
            weekStart: 0
        }
    }, a = e.fn.calendar.colors = ["#2C8FC9", "#9CB703", "#F5BB00", "#FF4A32", "#B56CE2", "#45A597"];
    e(function () {
        e('[data-provide="calendar"]').each(function () {
            e(this).calendar()
        })
    })
}(window.jQuery);