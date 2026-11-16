// export function normalizePreviewQuestion(q: any) {
//     return {
//         ...q,
//         options: (q.options || []).map((o: any) => ({
//             ...o,
//             zone_image: o.zone_image || o.zone_image_url || null,
//             image_url: o.image_url || null,
//         })),
//     };
// }

export function normalizePreviewQuestion(q: any) {
    return {
        ...q,
        timestamp_seconds: Number(q.timestamp_seconds),
        loop_seconds: Number(q.loop_seconds),
        correct_seek_to:
            q.correct_seek_to !== null ? Number(q.correct_seek_to) : null,
        wrong_seek_to:
            q.wrong_seek_to !== null ? Number(q.wrong_seek_to) : null,

        options: (q.options || []).map((o: any) => ({
            ...o,
            group_id: Number(o.group_id),
            is_correct: !!o.is_correct,

            coordinates: o.coordinates
                ? {
                      top: Number(o.coordinates.top),
                      left: Number(o.coordinates.left),
                      width: Number(o.coordinates.width),
                      height: Number(o.coordinates.height),
                  }
                : null,

            zone_image: o.zone_image || o.zone_image_url || null,
            image_url: o.image_url || null,
        })),
    };
}
