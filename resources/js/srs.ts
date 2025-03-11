import {Card, State} from "ts-fsrs";
import type {Review} from "./types";

/**
 * Convert a state to a string.
 * @param state The state to convert.
 */
export function stateToString(state: State): string {
    switch (state) {
        case State.New:
            return "new";
        case State.Learning:
            return "learning";
        case State.Review:
            return "review";
        case State.Relearning:
            return "relearning";
        default:
            return "new";
    }
}

/**
 * Convert a string to a state.
 * @param state The string to convert.
 */
export function stringToState(state: string): State {
    switch (state) {
        case "new":
            return State.New;
        case "learning":
            return State.Learning;
        case "review":
            return State.Review;
        case "relearning":
            return State.Relearning;
        default:
            return State.New;
    }
}

/**
 * Convert a review type to a FSRS card type.
 * @param review The review to convert.
 */
export function reviewToCard(review: Review): Card {
    return {
        due: review.due,
        stability: review.stability,
        difficulty: review.difficulty,
        elapsed_days: review.elapsed_days,
        scheduled_days: review.scheduled_days,
        reps: review.reps,
        lapses: review.lapses,
        state: stringToState(review.state),
        last_review: review.last_review,
    }
}

/**
 * Updates a review id with the card data.
 * @param review The review class to update.
 * @param card The card data to update with.
 */
export function updateReview(review: Review, card: Card): Review {
    return {
        ...review,
        due: card.due,
        stability: card.stability,
        difficulty: card.difficulty,
        elapsed_days: card.elapsed_days,
        scheduled_days: card.scheduled_days,
        reps: card.reps,
        lapses: card.lapses,
        state: stateToString(card.state),
        last_review: card.last_review,
    }
}
