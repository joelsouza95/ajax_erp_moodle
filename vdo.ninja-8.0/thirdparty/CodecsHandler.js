/*
The MIT License (MIT)

Copyright (c) 2012-2020 [Muaz Khan](https://github.com/muaz-khan)

    Permission is hereby granted, free of charge, to any person obtaining a copy of
    this software and associated documentation files (the "Software"), to deal in
    the Software without restriction, including without limitation the rights to
    use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
    the Software, and to permit persons to whom the Software is furnished to do so,
    subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
    FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
    COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
    IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
    CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    */
// Sourced from: https://cdn.webrtc-experiment.com/CodecsHandler.js

// *FILE HAS BEEN HEAVILY MODIFIED BY STEVE SEGUIN. ALL RIGHTS RESERVED WHERE APPLICABLE *

var CodecsHandler = (function() {
    function preferCodec(sdp, codecName) {
        var info = splitLines(sdp);

        if (!info.videoCodecNumbers) {
            return sdp;
        }

        if (codecName === 'vp8' && info.vp8LineNumber === info.videoCodecNumbers[0]) {
            return sdp;
        }

        if (codecName === 'vp9' && info.vp9LineNumber === info.videoCodecNumbers[0]) {
            return sdp;
        }

        if (codecName === 'h264' && info.h264LineNumber === info.videoCodecNumbers[0]) {
            return sdp;
        }

        sdp = preferCodecHelper(sdp, codecName, info);

        return sdp;
    }

    function preferCodecHelper(sdp, codec, info, ignore) {
        var preferCodecNumber = '';

        if (codec === 'vp8') {
            if (!info.vp8LineNumber) {
                return sdp;
            }
            preferCodecNumber = info.vp8LineNumber;
        }

        if (codec === 'vp9') {
            if (!info.vp9LineNumber) {
                return sdp;
            }
            preferCodecNumber = info.vp9LineNumber;
        }

        if (codec === 'h264') {
            if (!info.h264LineNumber) {
                return sdp;
            }

            preferCodecNumber = info.h264LineNumber;
        }

        var newLine = info.videoCodecNumbersOriginal.split('SAVPF')[0] + 'SAVPF ';

        var newOrder = [preferCodecNumber];

        if (ignore) {
            newOrder = [];
        }

        info.videoCodecNumbers.forEach(function(codecNumber) {
            if (codecNumber === preferCodecNumber) return;
            newOrder.push(codecNumber);
        });

        newLine += newOrder.join(' ');

        sdp = sdp.replace(info.videoCodecNumbersOriginal, newLine);
        return sdp;
    }

    function splitLines(sdp) {
        var info = {};
        sdp.split('\n').forEach(function(line) {
            if (line.indexOf('m=video') === 0) {
                info.videoCodecNumbers = [];
                line.split('SAVPF')[1].split(' ').forEach(function(codecNumber) {
                    codecNumber = codecNumber.trim();
                    if (!codecNumber || !codecNumber.length) return;
                    info.videoCodecNumbers.push(codecNumber);
                    info.videoCodecNumbersOriginal = line;
                });
            }

            if (line.indexOf('VP8/90000') !== -1 && !info.vp8LineNumber) {
                info.vp8LineNumber = line.replace('a=rtpmap:', '').split(' ')[0];
            }

            if (line.indexOf('VP9/90000') !== -1 && !info.vp9LineNumber) {
                info.vp9LineNumber = line.replace('a=rtpmap:', '').split(' ')[0];
            }

            if (line.indexOf('H264/90000') !== -1 && !info.h264LineNumber) {
                info.h264LineNumber = line.replace('a=rtpmap:', '').split(' ')[0];
            }
        });

        return info;
    }
    
    function extractSdp(sdpLine, pattern) {
        var result = sdpLine.match(pattern);
        return (result && result.length == 2)? result[1]: null;
    }

    function disableNACK(sdp) {
        if (!sdp || typeof sdp !== 'string') {
            throw 'Invalid arguments.';
        }

        sdp = sdp.replace('a=rtcp-fb:126 nack\r\n', '');
        sdp = sdp.replace('a=rtcp-fb:126 nack pli\r\n', 'a=rtcp-fb:126 pli\r\n');
        sdp = sdp.replace('a=rtcp-fb:97 nack\r\n', '');
        sdp = sdp.replace('a=rtcp-fb:97 nack pli\r\n', 'a=rtcp-fb:97 pli\r\n');

        return sdp;
    }

  
    // Find the line in sdpLines that starts with |prefix|, and, if specified,
    // contains |substr| (case-insensitive search).
    function findLine(sdpLines, prefix, substr) {
        return findLineInRange(sdpLines, 0, -1, prefix, substr);
    }

    // Find the line in sdpLines[startLine...endLine - 1] that starts with |prefix|
    // and, if specified, contains |substr| (case-insensitive search).
    function findLineInRange(sdpLines, startLine, endLine, prefix, substr) {
        var realEndLine = endLine !== -1 ? endLine : sdpLines.length;
        for (var i = startLine; i < realEndLine; ++i) {
            if (sdpLines[i].indexOf(prefix) === 0) {
                if (!substr ||
                    sdpLines[i].toLowerCase().indexOf(substr.toLowerCase()) !== -1) {
                    return i;
                }
            }
        }
        return null;
    }

    // Gets the codec payload type from an a=rtpmap:X line.
    function getCodecPayloadType(sdpLine) {
        var pattern = new RegExp('a=rtpmap:(\\d+) \\w+\\/\\d+');
        var result = sdpLine.match(pattern);
        return (result && result.length === 2) ? result[1] : null;
    }

    function getVideoBitrates(sdp) { 

        var sdpLines = sdp.split('\r\n');
        var mLineIndex = findLine(sdpLines, 'm=', 'video');
        if (mLineIndex === null) {
            return 2500;
        }
        var videoMLine = sdpLines[mLineIndex];
        var pattern = new RegExp('m=video\\s\\d+\\s[A-Z/]+\\s');
        var sendPayloadType = videoMLine.split(pattern)[1].split(' ')[0];
        var fmtpLine = sdpLines[findLine(sdpLines, 'a=rtpmap', sendPayloadType)];
        var codec = fmtpLine.split('a=rtpmap:' + sendPayloadType)[1].split('/')[0];

        var codecIndex = findLine(sdpLines, 'a=rtpmap', codec+'/90000');
        var codecPayload;
        if (codecIndex) {
            codecPayload = getCodecPayloadType(sdpLines[codecIndex]);
        }

        if (!codecPayload) {
            return 2500;
        }

        var rtxIndex = findLine(sdpLines, 'a=rtpmap', 'rtx/90000');
        var rtxPayload;
        if (rtxIndex) {
            rtxPayload = getCodecPayloadType(sdpLines[rtxIndex]);
        }

        if (!rtxIndex) {
            return 2500;
        }

        var rtxFmtpLineIndex = findLine(sdpLines, 'a=fmtp:' + rtxPayload.toString());
        if (rtxFmtpLineIndex !== null) {
            try {
                var maxBitrate = parseInt(sdpLines[rtxFmtpLineIndex].split("x-google-max-bitrate=")[1].split(";")[0]);
                var minBitrate = parseInt(sdpLines[rtxFmtpLineIndex].split("x-google-min-bitrate=")[1].split(";")[0]);
            } catch(e){
                return 2500;
            }
           
           if (minBitrate>maxBitrate){
               maxBitrate = minBitrate;
           }
           if (maxBitrate<1){maxBitrate=1;}
           return maxBitrate
        } else {
            return 2500;
        }

        
        
    }

    function setVideoBitrates(sdp, params, codec) {  // modified + Improved by Steve.
        
        if (codec){
            codec = codec.toUpperCase();
        } else{
            codec="VP8";
        }
        
        var sdpLines = sdp.split('\r\n');

        // Search for m line.
        var mLineIndex = findLine(sdpLines, 'm=', 'video');
        if (mLineIndex === null) {
            return sdp;
        }
        // Figure out the first codec payload type on the m=video SDP line.
        var videoMLine = sdpLines[mLineIndex];
        var pattern = new RegExp('m=video\\s\\d+\\s[A-Z/]+\\s');
        var sendPayloadType = videoMLine.split(pattern)[1].split(' ')[0];
        var fmtpLine = sdpLines[findLine(sdpLines, 'a=rtpmap', sendPayloadType)];
        var codecName = fmtpLine.split('a=rtpmap:' + sendPayloadType)[1].split('/')[0];
        
        codec = codecName || codec; // Try to find first Codec; else use expected/default
        
        params = params || {};
        var xgoogle_min_bitrate = params.min.toString();
        var xgoogle_max_bitrate = params.max.toString();

        var codecIndex = findLine(sdpLines, 'a=rtpmap', codec+'/90000');
        var codecPayload;
        if (codecIndex) {
            codecPayload = getCodecPayloadType(sdpLines[codecIndex]);
        }

        if (!codecPayload) {
            return sdp;
        }

        var rtxIndex = findLine(sdpLines, 'a=rtpmap', 'rtx/90000');
        var rtxPayload;
        if (rtxIndex) {
            rtxPayload = getCodecPayloadType(sdpLines[rtxIndex]);
        }

        if (!rtxIndex) {
            return sdp;
        }

        var rtxFmtpLineIndex = findLine(sdpLines, 'a=fmtp:' + rtxPayload.toString());
        if (rtxFmtpLineIndex !== null) {
            var appendrtxNext = '\r\n';
            appendrtxNext += 'a=fmtp:' + codecPayload + ' x-google-min-bitrate=' + (xgoogle_min_bitrate || '228') + '; x-google-max-bitrate=' + (xgoogle_max_bitrate || '228');
            sdpLines[rtxFmtpLineIndex] = sdpLines[rtxFmtpLineIndex].concat(appendrtxNext);
            sdp = sdpLines.join('\r\n');
        }

        return sdp;
    }

    function setOpusAttributes(sdp, params) {
        params = params || {};

        var sdpLines = sdp.split('\r\n');

        // Opus
        var opusIndex = findLine(sdpLines, 'a=rtpmap', 'opus/48000');
        var opusPayload;
        if (opusIndex) {
            opusPayload = getCodecPayloadType(sdpLines[opusIndex]);
        }

        if (!opusPayload) {
            return sdp;
        }

        var opusFmtpLineIndex = findLine(sdpLines, 'a=fmtp:' + opusPayload.toString());
        if (opusFmtpLineIndex === null) {
            return sdp;
        }

        var appendOpusNext = '';
        appendOpusNext += '; stereo=' + (typeof params.stereo != 'undefined' ? params.stereo : '1');
        appendOpusNext += '; sprop-stereo=' + (typeof params['sprop-stereo'] != 'undefined' ? params['sprop-stereo'] : '1');

        if (typeof params.maxaveragebitrate != 'undefined') {
            appendOpusNext += '; maxaveragebitrate=' + (params.maxaveragebitrate || 128 * 1024 * 8);
        }

        if (typeof params.maxplaybackrate != 'undefined') {
            appendOpusNext += '; maxplaybackrate=' + (params.maxplaybackrate || 128 * 1024 * 8);
        }

        if (typeof params.cbr != 'undefined') {
            appendOpusNext += '; cbr=' + (typeof params.cbr != 'undefined' ? params.cbr : '1');
        }

        if (typeof params.useinbandfec != 'undefined') {
            appendOpusNext += '; useinbandfec=' + params.useinbandfec;
        }

        if (typeof params.usedtx != 'undefined') {
            appendOpusNext += '; usedtx=' + params.usedtx;
        }

        if (typeof params.maxptime != 'undefined') {
            appendOpusNext += '\r\na=maxptime:' + params.maxptime;
        }

        sdpLines[opusFmtpLineIndex] = sdpLines[opusFmtpLineIndex].concat(appendOpusNext);

        sdp = sdpLines.join('\r\n');
        return sdp;
    }

    // forceStereoAudio => via webrtcexample.com
    // requires getUserMedia => echoCancellation:false
    function forceStereoAudio(sdp) {
        var sdpLines = sdp.split('\r\n');
        var fmtpLineIndex = null;
        for (var i = 0; i < sdpLines.length; i++) {
            if (sdpLines[i].search('opus/48000') !== -1) {
                var opusPayload = extractSdp(sdpLines[i], /:(\d+) opus\/48000/i);
                break;
            }
        }
        for (var i = 0; i < sdpLines.length; i++) {
            if (sdpLines[i].search('a=fmtp') !== -1) {
                var payload = extractSdp(sdpLines[i], /a=fmtp:(\d+)/);
                if (payload === opusPayload) {
                    fmtpLineIndex = i;
                    break;
                }
            }
        }
        if (fmtpLineIndex === null) return sdp;
        sdpLines[fmtpLineIndex] = sdpLines[fmtpLineIndex].concat('; stereo=1; sprop-stereo=1');
        sdp = sdpLines.join('\r\n');
        return sdp;
    }

    return {
        disableNACK: disableNACK,
        
		getVideoBitrates: function(sdp) {
            return getVideoBitrates(sdp);
        },
		
        setVideoBitrates: function(sdp, params, codec) {
            return setVideoBitrates(sdp, params, codec);
        },
        setOpusAttributes: function(sdp, params) {
            return setOpusAttributes(sdp, params);
        },

        preferCodec: preferCodec,
        
        forceStereoAudio: forceStereoAudio
    };
})();

