type Action = "continue" | "seek";

interface ResolveResult {
    result: "correct" | "wrong";
    action: Action;
    seekTo: number | null;
    targetLessonId: number | null; // 🔥 NOVO: Guardará o ID do vídeo destino
}

function pickAction(
    option: any | null,
    question: any,
    isCorrect: boolean
): { action: Action; seekTo: number | null; targetLessonId: number | null } {
    if (option) {
        if (isCorrect && option.correct_action) {
            return {
                action: option.correct_action,
                seekTo: option.correct_seek_to ?? null,
                targetLessonId: option.correct_target_lesson_id ?? null, // 🔥 NOVO
            };
        }

        if (!isCorrect && option.wrong_action) {
            return {
                action: option.wrong_action,
                seekTo: option.wrong_seek_to ?? null,
                targetLessonId: option.wrong_target_lesson_id ?? null, // 🔥 NOVO
            };
        }
    }

    if (isCorrect) {
        return {
            action: question.correct_action ?? "continue",
            seekTo: question.correct_seek_to ?? null,
            targetLessonId: question.correct_target_lesson_id ?? null, // 🔥 NOVO
        };
    }

    return {
        action: question.wrong_action ?? "continue",
        seekTo: question.wrong_seek_to ?? null,
        targetLessonId: question.wrong_target_lesson_id ?? null, // 🔥 NOVO
    };
}

export function resolveResponse(question: any, context: any): ResolveResult {
    // --------------------------------------------------
    // INFO / AUTOMATIC
    // --------------------------------------------------
    if (["info", "automatic"].includes(question.type)) {
        return {
            result: "correct",
            action: question.correct_action ?? "continue",
            seekTo: question.correct_seek_to ?? null,
            targetLessonId: question.correct_target_lesson_id ?? null, // 🔥 NOVO
        };
    }

    // --------------------------------------------------
    // DRAG AND DROP
    // --------------------------------------------------
    if (question.type === "drag_and_drop") {
        // 🔥 CORREÇÃO: Agora filtramos estritamente pela propriedade de ser zona,
        // aceitando tanto o formato que vem do banco (is_zone) quanto o injetado (_is_zone)
        const zones = question.options.filter(
            (o: any) => o.is_zone || o._is_zone
        );

        const ok = Object.entries(context.droppedItems || {}).every(
            ([zoneIdx, items]: any) =>
                items.every(
                    (i: number) =>
                        question.options[i].group_id ===
                        question.options[Number(zoneIdx)].group_id
                )
        );

        const action = pickAction(null, question, ok);

        return {
            result: ok ? "correct" : "wrong",
            action: action.action,
            seekTo: action.seekTo,
            targetLessonId: action.targetLessonId, // 🔥 NOVO
        };
    }

    // --------------------------------------------------
    // SINGLE / MULTIPLE
    // --------------------------------------------------
    const selected: number[] = context.selectedIndices || [];

    const correctIndices = question.options
        .map((o: any, i: number) => (o.is_correct ? i : -1))
        .filter((i: number) => i !== -1);

    const ok =
        selected.length === correctIndices.length &&
        selected.every((i: number) => correctIndices.includes(i));

    // 👉 opção “responsável”
    const decisiveOption =
        selected.length === 1 ? question.options[selected[0]] : null;

    const action = pickAction(decisiveOption, question, ok);

    return {
        result: ok ? "correct" : "wrong",
        action: action.action,
        seekTo: action.seekTo,
        targetLessonId: decisiveOption
            ? action.targetLessonId
            : ok
            ? question.correct_target_lesson_id
            : question.wrong_target_lesson_id,
    };
}
