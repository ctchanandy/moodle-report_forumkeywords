define([
  "core/config",
  "jquery",
  "report_forumkeywords/d3",
  "report_forumkeywords/cloud"
], function(mdlcfg, $, d3, cloud) {
  return {
    init: function(words, cid, fid) {
      var fill = d3.scaleOrdinal(d3.schemeCategory10);
      var w = parseInt(d3.select("#wordcloud").style("width"), 10);
      var h = parseInt(d3.select("#wordcloud").style("height"), 10);

      // min/max word size
      var maxSize = d3.max(words, function(d) {
        return d.size;
      });
      var minSize = d3.min(words, function(d) {
        return d.size;
      });
      // function to scale the word size within a range of font size
      var fontScale = d3
        .scaleLinear()
        .domain([minSize, maxSize])
        .range([20, 120]);

      cloud()
        .size([w, h])
        .words(words)
        .padding(2)
        .rotate(function() {
          return ~~(Math.random() * 2) * 90;
        })
        .rotate(function() {
          return 0;
        })
        .fontSize(function(d) {
          return fontScale(d.size);
        })
        .on("end", draw)
        .start();

      function draw(words) {
        d3.select("#wordcloud")
          .append("svg")
          .attr("width", w)
          .attr("height", h)
          .append("g")
          .attr("transform", "translate(" + w / 2 + "," + h / 2 + ")")
          .selectAll("text")
          .data(words)
          .enter()
          .append("text")
          .style("font-size", function(d) {
            return d.size + "px";
          })
          .style("font-family", "Microsoft JhengHei")
          .style("cursor", "pointer")
          .style("fill", function(d, i) {
            return fill(i);
          })
          .attr("text-anchor", "middle")
          .attr("transform", function(d) {
            return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
          })
          .text(function(d) {
            return d.text;
          })
          .on("click", function(d) {
            window.open(
              mdlcfg.wwwroot +
                "/mod/forum/search.php?id=" +
                cid +
                "&forumid=" +
                fid +
                "&search=" +
                d.text,
              "_blank"
            );
          });
      }

      $("#downloadpng").click(function() {
        var svgString = d3
          .select("svg")
          .attr("version", 1.1)
          .attr("xmlns", "http://www.w3.org/2000/svg")
          .node().parentNode.innerHTML;
        var imgsrc =
          "data:image/svg+xml;base64," +
          btoa(unescape(encodeURIComponent(svgString)));
        var canvas = document.querySelector("canvas"),
          context = canvas.getContext("2d");
        context.fillStyle = "white";
        context.fillRect(0, 0, canvas.width, canvas.height);

        var image = new Image();
        image.src = imgsrc;
        image.onload = function() {
          context.drawImage(image, 0, 0);
          var canvasdata = canvas.toDataURL("image/png");

          var a = document.createElement("a");
          a.download = "wordcloud.png";
          a.href = canvasdata;
          document.body.appendChild(a);
          a.click();
        };
      });
    }
  };
});
